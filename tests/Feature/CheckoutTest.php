<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Product $ream;

    private Product $pens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer(['special_discount' => 10])->create();
        $this->ream = Product::factory()->create(['designation' => 'Ramette A4', 'unit_price' => '49.90', 'stock' => 10]);
        $this->pens = Product::factory()->create(['designation' => 'Stylos', 'unit_price' => '12.50', 'stock' => 3]);
    }

    private function addToCart(Product $product, int $quantity): void
    {
        $this->post('/panier', ['product_id' => $product->id, 'quantity' => $quantity]);
    }

    private function checkout(string $terms = 'LIV', ?string $token = null)
    {
        return $this->post('/commande', [
            'payment_terms' => $terms,
            'checkout_token' => $token ?? (string) Str::uuid(),
        ]);
    }

    public function test_checkout_applies_client_discount_and_freezes_prices(): void
    {
        $this->actingAs($this->customer);
        $this->addToCart($this->ream, 3);  // 149.70
        $this->addToCart($this->pens, 2);  //  25.00

        $this->get('/panier')->assertSee('174,70')->assertSee('17,47')->assertSee('157,23');

        $response = $this->checkout('T30');

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertMatchesRegularExpression('/^Com-\d{6}$/', $order->number);
        $this->assertSame('T30', $order->payment_terms);
        $this->assertSame('pending', $order->status);
        $this->assertSame(10, $order->discount_rate);
        $this->assertSame('174.70', $order->subtotal);
        $this->assertSame('17.47', $order->discount_amount);
        $this->assertSame('157.23', $order->total);
        $this->assertCount(2, $order->lines);

        // Stock taken out, cart emptied
        $this->assertSame(7, $this->ream->fresh()->stock);
        $this->assertSame(1, $this->pens->fresh()->stock);
        $this->assertSame([], session('cart', []));

        // Confirmation page shows the order number
        $this->get(route('orders.show', $order))->assertSee($order->number)->assertSee('votre commande est confirmée');
    }

    public function test_historical_prices_and_discount_do_not_change(): void
    {
        $this->actingAs($this->customer);
        $this->addToCart($this->ream, 2);
        $this->checkout();
        $order = Order::firstOrFail();

        // Later: the catalog price and the client discount change
        $this->ream->update(['unit_price' => '99.00']);
        $this->customer->client->update(['special_discount' => 50]);

        $order->refresh()->load('lines');
        $this->assertSame('49.90', $order->lines->first()->unit_price);
        $this->assertSame(10, $order->discount_rate);
        $this->assertSame('89.82', $order->total); // 99.80 - 9.98
        $this->get(route('orders.show', $order))->assertSee('49,90')->assertDontSee('99,00');
    }

    public function test_insufficient_stock_is_refused_and_nothing_is_saved(): void
    {
        $this->actingAs($this->customer);

        // Adding more than the stock is refused immediately
        $this->addToCart($this->pens, 5);
        $this->assertSame([], session('cart', []));

        // Stock drops after the product was added to the cart
        $this->addToCart($this->ream, 2);
        $this->addToCart($this->pens, 3);
        $this->pens->update(['stock' => 1]);

        $this->checkout()->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $this->ream->fresh()->stock); // transaction rolled back
        $this->assertSame(1, $this->pens->fresh()->stock);
    }

    public function test_repeated_submission_creates_only_one_order(): void
    {
        $this->actingAs($this->customer);
        $this->addToCart($this->ream, 1);

        $token = (string) Str::uuid();
        $first = $this->checkout('LIV', $token);
        // The cart is now empty, but even with the same cart the same token returns the same order
        $this->addToCart($this->ream, 1);
        $second = $this->checkout('LIV', $token);

        $this->assertSame(1, Order::count());
        $this->assertSame($first->headers->get('Location'), $second->headers->get('Location'));
        $this->assertSame(9, $this->ream->fresh()->stock);
    }

    public function test_invalid_payment_terms_and_empty_cart(): void
    {
        $this->actingAs($this->customer);

        $this->get('/commande')->assertRedirect(route('cart.index'));

        $this->addToCart($this->ream, 1);
        $this->checkout('CASH')->assertSessionHasErrors('payment_terms');
        $this->assertSame(0, Order::count());
    }

    public function test_cancelling_restores_stock_only_once(): void
    {
        $this->actingAs($this->customer);
        $this->addToCart($this->ream, 4);
        $this->checkout();
        $order = Order::firstOrFail();
        $this->assertSame(6, $this->ream->fresh()->stock);

        // Customer cancels a pending order
        $this->post(route('orders.cancel', $order))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->stock_restored_at);
        $this->assertSame(10, $this->ream->fresh()->stock);

        // A second cancellation (customer or admin) is refused and stock is unchanged
        $this->post(route('orders.cancel', $order))->assertForbidden();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        $this->assertSame(10, $this->ream->fresh()->stock);
    }

    public function test_admin_status_workflow(): void
    {
        $this->actingAs($this->customer);
        $this->addToCart($this->ream, 1);
        $this->checkout();
        $order = Order::firstOrFail();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        foreach (['preparing', 'shipped', 'delivered'] as $status) {
            $this->patch(route('admin.orders.status', $order), ['status' => $status])->assertSessionHasNoErrors();
            $this->assertSame($status, $order->fresh()->status);
        }

        // Delivered is final: no cancel, no stock change
        $this->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertSessionHasErrors('status');
        $this->assertSame(9, $this->ream->fresh()->stock);
    }

    public function test_service_restores_stock_once_even_if_called_directly_twice(): void
    {
        $this->actingAs($this->customer);
        $this->addToCart($this->ream, 2);
        $this->checkout();
        $order = Order::firstOrFail();
        $service = app(OrderService::class);

        $service->changeStatus($order, 'cancelled');
        // Simulate a stale copy of the order still thinking it is "pending"
        $stale = Order::find($order->id);
        $stale->status = 'pending';
        $service->changeStatus($stale, 'cancelled');

        $this->assertSame(10, $this->ream->fresh()->stock);
    }

    public function test_guest_must_sign_in_before_checkout_but_keeps_cart(): void
    {
        $this->addToCart($this->ream, 2);
        $this->get('/commande')->assertRedirect(route('login'));

        $this->post('/connexion', ['email' => $this->customer->email, 'password' => 'password'])
            ->assertRedirect('/commande');
        $this->get('/panier')->assertSee('Ramette A4');
    }
}
