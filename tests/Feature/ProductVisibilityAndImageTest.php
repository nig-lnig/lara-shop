<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** "Visible dans la boutique" checkbox and product pictures. */
class ProductVisibilityAndImageTest extends TestCase
{
    use RefreshDatabase;

    private function productData(Supplier $supplier, array $overrides = []): array
    {
        return $overrides + [
            'designation' => 'Taille-crayon double', 'unit_price' => '4,50', 'family' => 'FO',
            'supplier_id' => $supplier->id, 'stock' => 20, 'low_stock_threshold' => 5,
        ];
    }

    public function test_admin_can_create_a_hidden_product_and_publish_it_later(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs($admin);

        // Checkbox not sent = not visible
        $this->post('/admin/produits', $this->productData($supplier))->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $this->assertFalse($product->is_listed);

        $this->get('/admin/produits?visibility=hidden')->assertSee('Taille-crayon double')->assertSee('masqué');
        $this->get('/admin/produits?visibility=listed')->assertDontSee('Taille-crayon double');
        $this->get('/produits/'.$product->code)->assertOk()->assertSee('Aperçu administrateur'); // admin preview

        $this->put("/admin/produits/{$product->id}", $this->productData($supplier, ['is_listed' => '1']))->assertSessionHasNoErrors();
        $this->assertTrue($product->fresh()->is_listed);
    }

    public function test_hidden_products_are_invisible_and_unorderable_for_customers(): void
    {
        $visible = Product::factory()->create(['designation' => 'Classeur visible']);
        $hidden = Product::factory()->create(['designation' => 'Classeur masqué', 'is_listed' => false]);
        $customer = User::factory()->customer()->create();
        $customer->favoriteProducts()->attach($hidden->id);

        $this->get('/')->assertSee('Classeur visible')->assertDontSee('Classeur masqué');
        $this->get('/?q=Classeur')->assertDontSee('Classeur masqué');
        $this->get('/produits/'.$hidden->code)->assertNotFound();
        $this->post('/panier', ['product_id' => $hidden->id])->assertNotFound();

        $this->actingAs($customer);
        $this->get('/produits/'.$hidden->code)->assertNotFound();
        $this->get('/favoris')->assertDontSee('Classeur masqué');
    }

    public function test_product_hidden_after_being_added_to_cart_blocks_checkout(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->actingAs(User::factory()->customer()->create());
        $this->post('/panier', ['product_id' => $product->id, 'quantity' => 2]);

        $product->update(['is_listed' => false]);
        // The cart no longer shows it, and a forced checkout is refused
        $this->get('/panier')->assertSee('Votre panier est vide');
        $this->post('/commande', ['payment_terms' => 'LIV', 'checkout_token' => (string) Str::uuid()])
            ->assertRedirect(route('cart.index'));
        $this->assertSame(0, Order::count());
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_uploaded_image_is_shown_with_the_address_the_visitor_uses(): void
    {
        Storage::fake('public');
        config(['app.url' => 'http://wrong-host.example']); // a wrong APP_URL must not break pictures
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)->post('/admin/produits', $this->productData($supplier, [
            'is_listed' => '1',
            'image' => UploadedFile::fake()->image('taille-crayon.png', 800, 600),
        ]))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        Storage::disk('public')->assertExists($product->image_path);

        // The visitor opens the shop with another address (e.g. a WAMP host): pictures follow it.
        $this->get('http://boutique.test/')
            ->assertSee('http://boutique.test/storage/'.$product->image_path, false)
            ->assertDontSee('wrong-host.example');
        $this->get('http://boutique.test/produits/'.$product->code)
            ->assertSee('http://boutique.test/storage/'.$product->image_path, false);
    }

    public function test_too_heavy_or_wrong_type_image_is_refused_in_french(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $supplier = Supplier::factory()->create();

        $this->post('/admin/produits', $this->productData($supplier, ['image' => UploadedFile::fake()->image('enorme.jpg')->size(6000)]))
            ->assertSessionHasErrors('image');
        $this->assertStringContainsString('Mo', session('errors')->first('image'));

        $this->post('/admin/produits', $this->productData($supplier, ['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');
        $this->assertSame(0, Product::count());
    }
}
