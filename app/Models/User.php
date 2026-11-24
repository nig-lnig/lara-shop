<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;

/**
 * A login account. Customers are linked to a Client record (client_id);
 * admins have role = 'admin' and no client.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CUSTOMER = 'customer';

    // role and client_id are NOT fillable: they are set explicitly in code,
    // so a form can never turn a customer into an admin.
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /**
     * Validation rules for an e-mail address, shared by sign-up and profile.
     *  - rfc + strict: follows the e-mail standard, no dubious characters
     *  - filter: PHP's own check (refuses "nom@domaine" without a dot, etc.)
     *  - dns: the domain must exist (only if SHOP_EMAIL_DNS_CHECK=true, needs internet)
     */
    public static function emailRules(?int $ignoreUserId = null): array
    {
        $format = 'email:rfc,strict,filter'.(config('shop.email_dns_check') ? ',dns' : '');

        return ['required', 'string', 'max:190', $format, Rule::unique('users', 'email')->ignore($ignoreUserId)];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER && $this->client_id !== null;
    }
}
