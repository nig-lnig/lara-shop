@extends('layouts.app')

@section('title', 'Mes favoris')

@section('content')
    <h1>Mon compte</h1>
    @include('partials.account-nav')

    @if ($products->isEmpty())
        <div class="empty-state card">
            <x-icon name="heart" />
            <h2>Aucun favori</h2>
            <p>Touchez le cœur sur un article pour le retrouver ici.</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">Parcourir le catalogue</a>
        </div>
    @else
        @php($favoriteIds = $products->pluck('id')->all())
        <div class="product-grid">
            @foreach ($products as $product)
                @include('catalog._card')
            @endforeach
        </div>
    @endif
@endsection
