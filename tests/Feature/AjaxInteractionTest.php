<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The JSON answers used by public/js/app.js (add to cart, favorites). */
class AjaxInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_cart_returns_json_with_the_new_count(): void
    {
        $product = Product::factory()->create(['stock' => 3]);

        $this->postJson('/panier', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['cartCount' => 2]);

        // Too many: 422 with a French message, the cart is unchanged
        $this->postJson('/panier', ['product_id' => $product->id, 'quantity' => 2])
            ->assertStatus(422)
            ->assertJson(['cartCount' => 2])
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Stock insuffisant'));
    }

    public function test_favorite_toggle_returns_json_state(): void
    {
        $user = User::factory()->customer()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->postJson("/favoris/{$product->id}")->assertOk()->assertJson(['favorite' => true]);
        $this->postJson("/favoris/{$product->id}")->assertOk()->assertJson(['favorite' => false]);
        $this->assertSame(0, $user->favoriteProducts()->count());
    }

    public function test_in_stock_filter_and_family_counts(): void
    {
        Product::factory()->create(['designation' => 'Disponible', 'stock' => 4]);
        Product::factory()->create(['designation' => 'Épuisé', 'stock' => 0]);

        $this->get('/?in_stock=1')->assertSee('Disponible')->assertDontSee('Épuisé')
            ->assertSee('En stock uniquement'); // active filter chip
    }
}
