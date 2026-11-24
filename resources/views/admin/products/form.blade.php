@extends('layouts.admin')

@section('title', $product->exists ? 'Modifier '.$product->code : 'Nouveau produit')

@section('content')
    <a href="{{ route('admin.products.index') }}" class="small-link"><x-icon name="arrow-left" /> Produits</a>
    <h1>{{ $product->exists ? 'Modifier le produit '.$product->code : 'Nouveau produit' }}</h1>

    {{-- enctype is required to upload a file --}}
    <form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
          method="post" enctype="multipart/form-data" class="card stack form-narrow">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        @unless ($product->exists)
            <p class="muted small">Le code produit (Pr-…) est généré automatiquement.</p>
        @endunless

        <x-input name="designation" label="Désignation" :value="$product->designation" required />
        <x-input name="description" label="Description" type="textarea" rows="3" :value="$product->description" />

        <div class="row-2">
            <x-input name="unit_price" label="Prix unitaire ({{ config('shop.currency') }})" :value="$product->unit_price"
                     required inputmode="decimal" help="Ex. 12,50 — les commandes passées gardent leur ancien prix." />
            <x-select name="family" label="Famille" required :value="$product->family" placeholder="— Choisir —"
                      :options="collect(App\Models\Product::FAMILIES)->map(fn ($label, $code) => $code.' – '.$label)->all()" />
        </div>

        <x-select name="supplier_id" label="Fournisseur" required :value="$product->supplier_id" placeholder="— Choisir —"
                  :options="$suppliers->mapWithKeys(fn ($s) => [$s->id => $s->name.' ('.$s->code.')'])->all()" />

        <div class="row-2">
            <x-input name="stock" label="Stock" type="number" min="0" step="1" :value="$product->stock" required />
            <x-input name="low_stock_threshold" label="Seuil d’alerte stock faible" type="number" min="0" step="1"
                     :value="$product->low_stock_threshold" required />
        </div>

        {{-- Picture: preview of the current picture, and of the new one BEFORE saving (public/js/app.js) --}}
        @php($maxKb = App\Http\Requests\ProductRequest::maxImageKb())
        <fieldset class="image-upload" data-image-upload data-max-bytes="{{ $maxKb * 1024 }}">
            <legend class="label">Photo du produit</legend>

            @unless (file_exists(public_path('storage')))
                <p class="alert alert-error"><x-icon name="alert" /> <span>Le lien <code>public/storage</code> est absent : lancez <code>php artisan storage:link</code>, sinon les images ne s’afficheront pas.</span></p>
            @endunless

            <div class="image-upload-grid">
                <div class="image-preview" data-preview>
                    @if ($product->exists)
                        <x-product-image :product="$product" />
                    @else
                        <div class="product-image product-image-md"><div class="image-fallback image-empty"><x-icon name="file" /><span class="fallback-label">Aucune image</span></div></div>
                    @endif
                    <img class="preview-new" data-preview-img alt="Aperçu de la nouvelle image" hidden>
                    <span class="preview-badge" data-preview-badge hidden>Aperçu · pas encore enregistré</span>
                </div>

                <div class="stack">
                    {{-- The real file input covers the whole box: click OR drag-and-drop a file onto it --}}
                    <label class="dropzone" for="image">
                        <x-icon name="download" />
                        <strong>Choisir une image</strong>
                        <span class="muted small">ou la glisser-déposer ici</span>
                        <span class="muted small">JPG, PNG ou WebP · {{ App\Http\Requests\ProductRequest::maxImageMb() }} Mo maximum · format 4:3 conseillé</span>
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="file-input"
                               @error('image') aria-invalid="true" aria-describedby="image-error" @enderror>
                    </label>
                    <p class="muted small" data-file-info aria-live="polite"></p>
                    <p class="field-error" data-file-error role="alert" hidden></p>
                    @error('image')
                        <p id="image-error" class="field-error"><x-icon name="alert" /> {{ $message }} Choisissez l’image à nouveau.</p>
                    @enderror
                    <button type="button" class="btn btn-ghost btn-sm" data-clear-file hidden><x-icon name="x" /> Annuler la nouvelle image</button>
                    @if ($product->image_path)
                        <label class="checkbox"><input type="checkbox" name="remove_image" value="1" data-remove-image> Supprimer l’image actuelle</label>
                    @endif
                </div>
            </div>
        </fieldset>

        {{-- Visibility for customers --}}
        <div class="toggle-field">
            <label class="switch-row">
                <input type="checkbox" name="is_listed" value="1" @checked(old('is_listed', $product->is_listed))>
                <span>
                    <strong>Visible dans la boutique</strong>
                    <small>Décochez pour masquer ce produit aux clients (catalogue, recherche, panier, commande).
                        Il reste visible ici et dans les commandes déjà passées.</small>
                </span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
