<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/** Excel (.xlsx) and PDF exports, plus the order PDF. */
class ExportFormatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@larashop.test')->first();
    }

    /** Saves a streamed .xlsx response to a temp file and opens it with PhpSpreadsheet. */
    private function workbook($response): Spreadsheet
    {
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());

        return IOFactory::load($path);
    }

    public function test_admin_xlsx_has_title_styled_header_and_real_numbers(): void
    {
        $this->actingAs($this->admin());
        $book = $this->workbook($this->get('/admin/exports/commandes.xlsx'));
        $sheet = $book->getActiveSheet();

        $this->assertSame('Commandes', $sheet->getTitle());
        $this->assertSame('Lara-Shop · Commandes', $sheet->getCell('A1')->getValue());
        $this->assertSame('N° commande', $sheet->getCell('A4')->getValue());
        $this->assertSame('14213D', $sheet->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertSame('A5', $sheet->getFreezePane());
        $this->assertSame(Order::count() + 4, $sheet->getHighestDataRow());

        // Column L = Total: a real number with a currency format
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('L5')->getDataType());
        $this->assertSame((float) Order::orderBy('id')->first()->total, (float) $sheet->getCell('L5')->getValue());
        $this->assertStringContainsString('DH', $sheet->getStyle('L5')->getNumberFormat()->getFormatCode());
    }

    public function test_xlsx_never_turns_user_text_into_a_formula_and_keeps_leading_zeros(): void
    {
        Client::create(['name' => '=1+1', 'street' => 'x', 'postal_code' => '04600', 'city' => 'Safi', 'type' => 'DI', 'special_discount' => 0]);
        $this->actingAs($this->admin());

        $sheet = $this->workbook($this->get('/admin/exports/clients.xlsx?q=04600'))->getActiveSheet();
        // searching "04600" matches nothing by name/code/city, so search by name instead
        $sheet = $this->workbook($this->get('/admin/exports/clients.xlsx?q='.urlencode('=1+1')))->getActiveSheet();

        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B5')->getDataType());
        $this->assertSame('=1+1', $sheet->getCell('B5')->getValue());
        $this->assertSame('04600', $sheet->getCell('D5')->getValue());
    }

    public function test_admin_complete_workbook_has_one_tab_per_dataset(): void
    {
        $this->actingAs($this->admin());
        $book = $this->workbook($this->get('/admin/exports/lara-shop-classeur-complet.xlsx'));

        $this->assertSame(
            ['Clients', 'Fournisseurs', 'Produits et stock', 'Commandes', 'Lignes de commande', 'Favoris des clients', 'Comptes utilisateurs'],
            $book->getSheetNames()
        );
    }

    public function test_admin_pdf_exports_including_filtered(): void
    {
        $this->actingAs($this->admin());

        foreach (['clients', 'fournisseurs', 'produits', 'commandes', 'lignes-commande', 'favoris', 'comptes'] as $dataset) {
            $response = $this->get("/admin/exports/{$dataset}.pdf")->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }

        $filtered = $this->get('/admin/exports/commandes.pdf?status=pending')->assertOk();
        $this->assertStringContainsString('commandes-filtre-', $filtered->headers->get('Content-Disposition'));
        $this->get('/admin/exports/commandes.docx')->assertNotFound();
    }

    public function test_customer_xlsx_and_pdf_are_scoped_to_their_data(): void
    {
        $user = User::where('email', 'client@larashop.test')->first();
        $this->actingAs($user);

        $sheet = $this->workbook($this->get('/mes-donnees/commandes.xlsx'))->getActiveSheet();
        $this->assertSame(Order::where('client_id', $user->client_id)->count() + 4, $sheet->getHighestDataRow());
        foreach (range(5, $sheet->getHighestDataRow()) as $row) {
            $this->assertSame('Cli-000001', $sheet->getCell("C{$row}")->getValue());
        }

        $book = $this->workbook($this->get('/mes-donnees/classeur.xlsx'));
        $this->assertSame(['Profil et adresse', 'Commandes', 'Lignes de commande', 'Produits favoris'], $book->getSheetNames());

        foreach (['/mes-donnees/rapport.pdf', '/mes-donnees/favoris.pdf'] as $url) {
            $this->assertStringStartsWith('%PDF-', $this->get($url)->assertOk()->getContent());
        }

        // Customers cannot reach the store-wide exports
        $this->get('/admin/exports/commandes.xlsx')->assertForbidden();
        $this->get('/admin/exports/lara-shop-classeur-complet.xlsx')->assertForbidden();
    }

    public function test_order_pdf_respects_ownership(): void
    {
        $own = Order::where('number', 'Com-000001')->first();     // Cli-000001
        $other = Order::where('number', 'Com-000004')->first();   // Cli-000002

        $this->actingAs(User::where('email', 'client@larashop.test')->first());
        $response = $this->get(route('orders.pdf', $own))->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('commande-Com-000001.pdf', $response->headers->get('Content-Disposition'));
        $this->get(route('orders.pdf', $other))->assertForbidden();

        $this->actingAs($this->admin())->get(route('admin.orders.pdf', $other))->assertOk();
    }
}
