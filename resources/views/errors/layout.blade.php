{{-- Minimal error page (no database / session needed). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="container error-page">
    <p class="error-code">@yield('code')</p>
    <h1>@yield('title')</h1>
    <p class="muted">@yield('message')</p>
    <a href="{{ url('/') }}" class="btn btn-primary">Retour à la boutique</a>
</main>
</body>
</html>
