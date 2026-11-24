<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Used by the automated tests.
 *   User::factory()->customer()->create()  -> customer with its own Client
 *   User::factory()->admin()->create()     -> administrator
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROLE_CUSTOMER,
            'client_id' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN, 'client_id' => null]);
    }

    public function customer(array $clientAttributes = []): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_CUSTOMER,
            'client_id' => Client::factory()->create($clientAttributes)->id,
        ]);
    }
}
