<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_linked_client_with_default_type_and_no_discount(): void
    {
        $response = $this->post('/inscription', [
            'name' => 'Nadia Alaoui',
            'email' => 'nadia@example.com',
            'password' => 'Cahier#Bleu2026',
            'password_confirmation' => 'Cahier#Bleu2026',
            'street' => '1 rue de la Plage',
            'postal_code' => '04600', // leading zero must be kept
            'city' => 'Safi',
            // Attempts to choose privileged values are ignored:
            'role' => 'admin',
            'special_discount' => 90,
            'type' => 'PU',
        ]);

        $response->assertRedirect(route('catalog.index'));
        $this->assertAuthenticated();

        $user = User::where('email', 'nadia@example.com')->first();
        $this->assertSame('customer', $user->role);
        $this->assertNotNull($user->client);
        $this->assertMatchesRegularExpression('/^Cli-\d{6}$/', $user->client->code);
        $this->assertSame('PA', $user->client->type);
        $this->assertSame(0, $user->client->special_discount);
        $this->assertSame('04600', $user->client->postal_code);
        $this->assertNotSame('Cahier#Bleu2026', $user->password); // stored hashed
    }

    public function test_registration_validates_input_in_french(): void
    {
        $this->from('/inscription')->post('/inscription', ['email' => 'not-an-email'])
            ->assertRedirect('/inscription')
            ->assertSessionHasErrors(['name', 'email', 'password', 'street', 'postal_code', 'city']);

        $this->assertStringContainsString('obligatoire', session('errors')->first('name'));
    }

    private function registerWith(array $overrides)
    {
        return $this->from('/inscription')->post('/inscription', $overrides + [
            'name' => 'Test', 'email' => 'test@example.com',
            'password' => 'Cahier#Bleu2026', 'password_confirmation' => 'Cahier#Bleu2026',
            'street' => '1 rue', 'postal_code' => '46000', 'city' => 'Safi',
        ]);
    }

    public function test_weak_passwords_are_refused_with_the_missing_rule(): void
    {
        $cases = [
            'Court#1a' => 'au moins 10 caractères',          // too short
            'toutminuscule#2026' => 'une majuscule et une minuscule',
            'SansChiffre#Bleu' => 'au moins un chiffre',
            'SansSymbole2026Ab' => 'au moins un symbole',
        ];

        foreach ($cases as $password => $expected) {
            $this->registerWith(['password' => $password, 'password_confirmation' => $password])
                ->assertSessionHasErrors('password');
            $this->assertStringContainsString($expected, implode(' ', session('errors')->get('password')), $password);
        }
        $this->assertGuest();
    }

    public function test_invalid_emails_are_refused(): void
    {
        foreach (['nadia@localhost', 'na dia@example.com', 'nadia@@example.com', 'nadia@exemple', 'nadia.example.com'] as $email) {
            $this->registerWith(['email' => $email])->assertSessionHasErrors('email');
        }
        $this->assertSame(0, User::count());
    }

    public function test_email_is_normalised_and_unique_whatever_the_case(): void
    {
        $this->registerWith(['email' => '  Nadia.Alaoui@Example.COM '])->assertSessionHasNoErrors();
        $this->assertSame('nadia.alaoui@example.com', User::first()->email);

        $this->post('/deconnexion');
        $this->registerWith(['email' => 'NADIA.ALAOUI@example.com'])->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_a_strong_and_different_password(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user);

        $this->put('/profil/mot-de-passe', ['current_password' => 'password', 'password' => 'faible', 'password_confirmation' => 'faible'])
            ->assertSessionHasErrors('password');
        $this->put('/profil/mot-de-passe', ['current_password' => 'password', 'password' => 'Nouveau#Mot2026', 'password_confirmation' => 'Nouveau#Mot2026'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Nouveau#Mot2026', $user->fresh()->password));
    }

    public function test_customer_can_sign_in_and_out(): void
    {
        $user = User::factory()->customer()->create(['email' => 'c@example.com']);

        $this->post('/connexion', ['email' => 'c@example.com', 'password' => 'password'])
            ->assertRedirect(route('catalog.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/deconnexion')->assertRedirect(route('catalog.index'));
        $this->assertGuest();
    }

    public function test_admin_is_sent_to_the_dashboard_after_sign_in(): void
    {
        User::factory()->admin()->create(['email' => 'a@example.com']);

        $this->post('/connexion', ['email' => 'a@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->customer()->create(['email' => 'c@example.com']);

        $this->from('/connexion')->post('/connexion', ['email' => 'c@example.com', 'password' => 'wrong'])
            ->assertRedirect('/connexion')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_customer_updates_address_but_cannot_change_type_or_discount(): void
    {
        $user = User::factory()->customer(['special_discount' => 5, 'type' => 'LI'])->create();

        $this->actingAs($user)->put('/profil', [
            'name' => 'Nouveau Nom',
            'email' => $user->email,
            'street' => '99 avenue Mohammed V',
            'postal_code' => '46000',
            'city' => 'Safi',
            'special_discount' => 100,
            'type' => 'PU',
        ])->assertSessionHasNoErrors();

        $client = $user->client->fresh();
        $this->assertSame('99 avenue Mohammed V', $client->street);
        $this->assertSame(5, $client->special_discount);
        $this->assertSame('LI', $client->type);
    }
}
