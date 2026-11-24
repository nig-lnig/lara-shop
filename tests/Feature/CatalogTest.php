<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Product::factory()->create(['designation' => 'Cahier rouge', 'family' => 'PA', 'unit_price' => '12.00']);
        Product::factory()->create(['designation' => 'Agrafeuse', 'family' => 'BU', 'unit_price' => '45.00']);
        Product::factory()->create(['designation' => 'Dictionnaire', 'family' => 'LI', 'unit_price' => '120.00']);
        $archived = Product::factory()->create(['designation' => 'Cahier archivé', 'family' => 'PA']);
        $archived->archive();
    }

    public function test_catalog_lists_active_products_only(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cahier rouge')
            ->assertSee('Agrafeuse')
            ->assertDontSee('Cahier archivé');
    }

    public function test_search_by_name_and_by_code(): void
    {
        $this->get('/?q=cahier')->assertSee('Cahier rouge')->assertDontSee('Agrafeuse');

        $code = Product::where('designation', 'Agrafeuse')->value('code');
        $this->get('/?q='.$code)->assertSee('Agrafeuse')->assertDontSee('Cahier rouge');
    }

    public function test_family_and_price_filters(): void
    {
        $this->get('/?family=LI')->assertSee('Dictionnaire')->assertDontSee('Agrafeuse');
        $this->get('/?min_price=20&max_price=50')->assertSee('Agrafeuse')
            ->assertDontSee('Cahier rouge')->assertDontSee('Dictionnaire');
    }

    public function test_sorting_by_price(): void
    {
        $this->get('/?sort=price_desc')->assertSeeInOrder(['Dictionnaire', 'Agrafeuse', 'Cahier rouge']);
        $this->get('/?sort=price_asc')->assertSeeInOrder(['Cahier rouge', 'Agrafeuse', 'Dictionnaire']);
    }

    public function test_invalid_filters_are_ignored(): void
    {
        $this->get('/?family=XX&sort=hack&min_price=abc&q[]=1')->assertOk()->assertSee('Cahier rouge');
    }

    public function test_pagination(): void
    {
        Product::factory()->count(15)->create();

        $this->get('/')->assertOk()->assertSee('Suivant');
        $this->get('/?page=2')->assertOk();
    }

    public function test_hero_is_shown_only_on_the_shop_home_page(): void
    {
        $this->get('/')->assertSee('Tout pour écrire, ranger et apprendre')->assertSee('images/hero-lara-shop.svg', false);
        $this->get('/?family=PA')->assertDontSee('Tout pour écrire, ranger et apprendre');
        $this->get('/?page=2')->assertDontSee('Tout pour écrire, ranger et apprendre');
    }

    public function test_old_catalogue_address_redirects_with_its_filters(): void
    {
        $this->get('/catalogue?family=BU')->assertRedirect('/?family=BU');
    }

    public function test_product_detail_page_and_archived_product_404(): void
    {
        $product = Product::where('designation', 'Agrafeuse')->first();
        $this->get('/produits/'.$product->code)->assertOk()->assertSee('Agrafeuse')->assertSee('45,00');

        $archived = Product::where('designation', 'Cahier archivé')->first();
        $this->get('/produits/'.$archived->code)->assertNotFound();
    }
}
