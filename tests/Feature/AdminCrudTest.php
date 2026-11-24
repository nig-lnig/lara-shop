<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_create_product_with_generated_code_and_image(): void
    {
        Storage::fake('public');
        $supplier = Supplier::factory()->create();

        $this->post('/admin/produits', [
            'designation' => 'Taille-crayon',
            'unit_price' => '3,50', // French decimal comma accepted
            'family' => 'FO',
            'supplier_id' => $supplier->id,
            'stock' => 20,
            'low_stock_threshold' => 5,
            'image' => UploadedFile::fake()->image('taille.jpg'),
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertMatchesRegularExpression('/^Pr-\d{6}$/', $product->code);
        $this->assertSame('3.50', $product->unit_price);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_validation(): void
    {
        $archivedSupplier = Supplier::factory()->create();
        $archivedSupplier->archive();

        $this->post('/admin/produits', [
            'designation' => '', 'unit_price' => '1.999', 'family' => 'ZZ',
            'supplier_id' => $archivedSupplier->id, 'stock' => -1, 'low_stock_threshold' => 5,
        ])->assertSessionHasErrors(['designation', 'unit_price', 'family', 'supplier_id', 'stock']);
    }

    public function test_client_crud_and_discount_range(): void
    {
        $this->post('/admin/clients', [
            'name' => 'Mairie', 'street' => '1 place', 'postal_code' => '46000', 'city' => 'Safi',
            'type' => 'PU', 'special_discount' => 101,
        ])->assertSessionHasErrors('special_discount');

        $this->post('/admin/clients', [
            'name' => 'Mairie', 'street' => '1 place', 'postal_code' => '46000', 'city' => 'Safi',
            'type' => 'PU', 'special_discount' => 15,
        ])->assertSessionHasNoErrors();

        $client = Client::firstOrFail();
        $this->assertSame('Cli-000001', $client->code);

        $this->put("/admin/clients/{$client->id}", [
            'name' => 'Mairie de Safi', 'street' => '1 place', 'postal_code' => '46000', 'city' => 'Safi',
            'type' => 'AS', 'special_discount' => 20,
        ])->assertSessionHasNoErrors();
        $this->assertSame(20, $client->fresh()->special_discount);
    }

    public function test_referenced_records_are_archived_not_deleted(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $order = Order::create([
            'order_date' => now(), 'payment_terms' => 'LIV', 'client_id' => $customer->client_id, 'status' => 'delivered',
            'discount_rate' => 0, 'subtotal' => '10.00', 'discount_amount' => '0.00', 'total' => '10.00',
        ]);
        $order->lines()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '10.00', 'line_total' => '10.00']);

        $this->delete("/admin/produits/{$product->id}");
        $this->delete("/admin/fournisseurs/{$product->supplier_id}");
        $this->delete("/admin/clients/{$customer->client_id}");

        $this->assertTrue($product->fresh()->isArchived());
        $this->assertTrue($product->supplier->fresh()->isArchived());
        $this->assertTrue($customer->client->fresh()->isArchived());
        $this->assertSame(1, $order->lines()->count()); // history intact

        // Archived product disappears from the shop, and can be restored
        $this->get('/')->assertDontSee($product->designation);
        $this->patch("/admin/produits/{$product->id}/restaurer");
        $this->assertFalse($product->fresh()->isArchived());
    }

    public function test_unreferenced_records_are_really_deleted(): void
    {
        $supplier = Supplier::factory()->create();
        $this->delete("/admin/fournisseurs/{$supplier->id}");
        $this->assertModelMissing($supplier);
    }

    public function test_order_line_pair_is_unique_in_database(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $order = Order::create([
            'order_date' => now(), 'payment_terms' => 'LIV', 'client_id' => $customer->client_id, 'status' => 'pending',
            'discount_rate' => 0, 'subtotal' => '10.00', 'discount_amount' => '0.00', 'total' => '10.00',
        ]);
        $order->lines()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '10.00', 'line_total' => '10.00']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $order->lines()->create(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '10.00', 'line_total' => '20.00']);
    }
}
