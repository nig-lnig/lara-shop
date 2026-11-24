<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function csv($response): string
    {
        $response->assertOk();

        return $response->streamedContent();
    }

    public function test_customer_exports_contain_only_their_own_data(): void
    {
        $user = User::where('email', 'client@larashop.test')->first(); // Cli-000001
        $this->actingAs($user);

        $orders = $this->csv($this->get('/mes-donnees/commandes.csv'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $orders); // UTF-8 BOM for Excel
        $this->assertStringContainsString('"N° commande";"Date commande"', $orders);
        $this->assertStringContainsString('Com-000001', $orders);
        $this->assertStringNotContainsString('Cli-000002', $orders);
        $this->assertSame(3 + 1, substr_count(trim($orders), "\n") + 1); // heading + 3 orders

        $lines = $this->csv($this->get('/mes-donnees/lignes-commande.csv'));
        $this->assertStringContainsString('45,00', $lines); // historical price, French decimal
        $this->assertStringNotContainsString('Com-000004', $lines); // order of another client

        $profile = $this->csv($this->get('/mes-donnees/profil.csv'));
        $this->assertStringContainsString('client@larashop.test', $profile);
        $this->assertStringContainsString('Particulier', $profile);

        $favorites = $this->csv($this->get('/mes-donnees/favoris.csv'));
        $this->assertStringNotContainsString('youssef@larashop.test', $favorites);
        $this->assertSame(3 + 1, substr_count(trim($favorites), "\n") + 1);

        foreach ([$orders, $lines, $profile, $favorites] as $content) {
            $this->assertStringNotContainsString('$2y$', $content); // no bcrypt hash
            $this->assertStringNotContainsStringIgnoringCase('password', $content);
        }

        $this->get('/mes-donnees/inconnu.csv')->assertNotFound();
    }

    public function test_customer_zip_bundle(): void
    {
        $this->actingAs(User::where('email', 'client@larashop.test')->first());

        $response = $this->get('/mes-donnees/archive.zip')->assertOk();
        $zip = new ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        sort($names);
        $this->assertSame([
            'mes-donnees-commandes.csv', 'mes-donnees-favoris.csv', 'mes-donnees-lignes-commande.csv', 'mes-donnees-profil.csv',
        ], $names);
    }

    public function test_admin_exports_every_dataset_without_secrets(): void
    {
        $this->actingAs(User::where('email', 'admin@larashop.test')->first());

        foreach (['clients', 'fournisseurs', 'produits', 'commandes', 'lignes-commande', 'favoris', 'comptes'] as $dataset) {
            $content = $this->csv($this->get("/admin/exports/{$dataset}.csv?archived=all"));
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
            $this->assertStringNotContainsString('$2y$', $content);
            $this->assertStringNotContainsStringIgnoringCase('remember', $content);
            $this->assertStringNotContainsStringIgnoringCase('password', $content);
        }

        $orders = $this->csv($this->get('/admin/exports/commandes.csv'));
        $this->assertSame(Order::count() + 1, substr_count(trim($orders), "\n") + 1);
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $orders); // readable dates
    }

    public function test_admin_filtered_export_matches_the_list_filters(): void
    {
        $this->actingAs(User::where('email', 'admin@larashop.test')->first());

        $response = $this->get('/admin/exports/commandes.csv?status=pending');
        $content = $this->csv($response);
        $this->assertStringContainsString('-filtre-', $response->headers->get('Content-Disposition'));
        $this->assertSame(Order::where('status', 'pending')->count() + 1, substr_count(trim($content), "\n") + 1);
        $this->assertStringNotContainsString('Livrée', $content);

        $products = $this->csv($this->get('/admin/exports/produits.csv?stock=low'));
        $this->assertStringContainsString('Étiqueteuse portable', $products);
        $this->assertStringNotContainsString('Gomme blanche', $products);
    }

    public function test_admin_complete_bundle(): void
    {
        $this->actingAs(User::where('email', 'admin@larashop.test')->first());

        $response = $this->get('/admin/exports/lara-shop-export-complet.zip')->assertOk();
        $zip = new ZipArchive;
        $zip->open($response->getFile()->getPathname());
        $this->assertSame(7, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('lignes-commande.csv'));
        $this->assertNotFalse($zip->locateName('comptes-utilisateurs.csv'));
    }

    public function test_formula_injection_is_neutralised(): void
    {
        Client::create([
            'name' => '=HYPERLINK("http://evil.test","clic")', 'street' => '+33 rue', 'postal_code' => '46000',
            'city' => '@SUM(A1)', 'type' => 'DI', 'special_discount' => 0,
        ]);
        $this->actingAs(User::where('email', 'admin@larashop.test')->first());

        $content = $this->csv($this->get('/admin/exports/clients.csv'));
        $this->assertStringContainsString('"\'=HYPERLINK(""http://evil.test"",""clic"")"', $content);
        $this->assertStringContainsString("'+33 rue", $content);
        $this->assertStringContainsString("'@SUM(A1)", $content);
    }
}
