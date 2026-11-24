<?php

namespace App\Providers;

use App\Services\Cart;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Our own pagination HTML (resources/views/partials/pagination.blade.php).
        Paginator::defaultView('partials.pagination');

        // One password policy for the whole app: Password::defaults() in the controllers.
        // At least 10 characters, upper and lower case letters, a digit and a symbol.
        Password::defaults(function () {
            $rule = Password::min(config('shop.password_min_length'))->letters()->mixedCase()->numbers()->symbols();

            return config('shop.password_leak_check') && ! app()->runningUnitTests() ? $rule->uncompromised() : $rule;
        });

        // Every page using the storefront layout receives $cartCount (badge in the header).
        View::composer('layouts.app', function ($view) {
            $view->with('cartCount', app(Cart::class)->count());
        });
    }
}
