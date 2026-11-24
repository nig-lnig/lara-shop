<?php

/*
 * Lara-Shop settings. Read with config('shop.currency'), etc.
 */
return [
    'name' => 'Lara-Shop',
    'currency' => env('SHOP_CURRENCY', 'DH'),
    'per_page' => 12,          // products per catalog page
    'admin_per_page' => 15,    // rows per admin table page
    'max_quantity' => 99,      // max quantity of one product in the cart

    // Account security (see App\Providers\AppServiceProvider and User::emailRules())
    'password_min_length' => 10,
    // Also refuse passwords found in public data leaks (haveibeenpwned.com, needs internet;
    // if the site cannot be reached, the check is skipped).
    'password_leak_check' => env('SHOP_PASSWORD_LEAK_CHECK', true),
    // Also check that the e-mail domain really exists (DNS, needs internet).
    'email_dns_check' => env('SHOP_EMAIL_DNS_CHECK', false),
];
