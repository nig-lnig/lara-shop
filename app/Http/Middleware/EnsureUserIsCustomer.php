<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route middleware "customer": only accounts linked to a client record. */
class EnsureUserIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isCustomer(), 403, 'Cette page est réservée aux comptes clients.');

        return $next($request);
    }
}
