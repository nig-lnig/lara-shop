<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $user): Order
    {
        return Order::create([
            'order_date' => now(), 'payment_terms' => 'LIV', 'client_id' => $user->client_id,
            'status' => 'pending', 'discount_rate' => 0, 'subtotal' => '10.00',
            'discount_amount' => '0.00', 'total' => '10.00',
        ]);
    }

    public function test_customer_cannot_open_another_customers_order_by_url(): void
    {
        $alice = User::factory()->customer()->create();
        $bob = User::factory()->customer()->create();
        $bobOrder = $this->orderFor($bob);

        $this->actingAs($alice);
        $this->get(route('orders.show', $bobOrder))->assertForbidden();
        $this->get(route('orders.print', $bobOrder))->assertForbidden();
        $this->post(route('orders.cancel', $bobOrder))->assertForbidden();
        $this->assertSame('pending', $bobOrder->fresh()->status);

        $this->actingAs($bob)->get(route('orders.show', $bobOrder))->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/mes-commandes', '/profil', '/mes-donnees', '/mes-donnees/commandes.csv', '/admin', '/admin/exports/clients.csv'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_customers_cannot_reach_admin_pages_or_exports(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer);

        foreach (['/admin', '/admin/clients', '/admin/produits', '/admin/commandes', '/admin/exports',
            '/admin/exports/clients.csv', '/admin/exports/lara-shop-export-complet.zip'] as $url) {
            $this->get($url)->assertForbidden();
        }

        $order = $this->orderFor($customer);
        $this->patch(route('admin.orders.status', $order), ['status' => 'delivered'])->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_admin_can_see_all_orders_but_cannot_use_customer_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->orderFor(User::factory()->customer()->create());

        $this->actingAs($admin);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->number);
        $this->get('/commande')->assertForbidden();
        $this->get('/mes-donnees')->assertForbidden();
    }

    public function test_all_admin_pages_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@larashop.test')->first();
        $this->actingAs($admin);

        foreach (['/admin', '/admin/clients', '/admin/clients/create', '/admin/clients/1/edit',
            '/admin/fournisseurs', '/admin/fournisseurs/create', '/admin/fournisseurs/1/edit',
            '/admin/produits', '/admin/produits/create', '/admin/produits/1/edit', '/admin/produits?stock=low',
            '/admin/commandes', '/admin/commandes?status=pending&payment_terms=T30', '/admin/commandes/Com-000001',
            '/admin/exports'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_all_customer_pages_render(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'client@larashop.test')->first());

        foreach (['/', '/produits/Pr-000001', '/panier', '/favoris', '/mes-commandes',
            '/mes-commandes/Com-000001', '/mes-commandes/Com-000001/imprimer', '/mes-donnees', '/profil'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
