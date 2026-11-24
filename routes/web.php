<?php

use App\Http\Controllers\AccountDataController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Lara-Shop routes
|--------------------------------------------------------------------------
| 1. Public pages (catalog, cart)
| 2. Guest pages (sign up / sign in)
| 3. Customer pages  -> middleware "auth" + "customer"
| 4. Admin pages     -> middleware "auth" + "admin", URL prefix /admin
*/

// ---- 1. Public -------------------------------------------------------------
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
// Old address of the catalog: keep links working (filters included).
Route::get('/catalogue', fn (Request $request) => redirect()->route('catalog.index', $request->query()));
Route::get('/produits/{product:code}', [CatalogController::class, 'show'])->name('catalog.show');

// The cart lives in the session, so guests can fill it before signing in.
Route::get('/panier', [CartController::class, 'index'])->name('cart.index');
Route::post('/panier', [CartController::class, 'store'])->name('cart.store');
Route::patch('/panier/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

// ---- 2. Guests only --------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store']);
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('/deconnexion', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---- 3. Customers ----------------------------------------------------------
Route::middleware(['auth', 'customer'])->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/commande', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/commande', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/mes-commandes', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mes-commandes/{order:number}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/mes-commandes/{order:number}/imprimer', [OrderController::class, 'print'])->name('orders.print');
    Route::get('/mes-commandes/{order:number}/pdf', [OrderController::class, 'pdf'])->name('orders.pdf');
    Route::post('/mes-commandes/{order:number}/annuler', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/favoris', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favoris/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/mes-donnees', [AccountDataController::class, 'index'])->name('my-data.index');
    Route::get('/mes-donnees/archive.zip', [AccountDataController::class, 'bundle'])->name('my-data.bundle');
    Route::get('/mes-donnees/classeur.xlsx', [AccountDataController::class, 'workbook'])->name('my-data.workbook');
    Route::get('/mes-donnees/rapport.pdf', [AccountDataController::class, 'report'])->name('my-data.report');
    // One file in one format: /mes-donnees/commandes.csv, .xlsx or .pdf
    Route::get('/mes-donnees/{dataset}.{format}', [AccountDataController::class, 'download'])
        ->whereIn('format', AccountDataController::FORMATS)->name('my-data.download');
});

// ---- 4. Admin --------------------------------------------------------------
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', Admin\ClientController::class)->except('show');
    Route::patch('clients/{client}/restaurer', [Admin\ClientController::class, 'restore'])->name('clients.restore');

    Route::resource('fournisseurs', Admin\SupplierController::class)
        ->parameters(['fournisseurs' => 'supplier'])->names('suppliers')->except('show');
    Route::patch('fournisseurs/{supplier}/restaurer', [Admin\SupplierController::class, 'restore'])->name('suppliers.restore');

    Route::resource('produits', Admin\ProductController::class)
        ->parameters(['produits' => 'product'])->names('products')->except('show');
    Route::patch('produits/{product}/restaurer', [Admin\ProductController::class, 'restore'])->name('products.restore');

    Route::get('commandes', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('commandes/{order:number}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::get('commandes/{order:number}/pdf', [Admin\OrderController::class, 'pdf'])->name('orders.pdf');
    Route::patch('commandes/{order:number}/statut', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');

    Route::get('exports', [Admin\ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/lara-shop-export-complet.zip', [Admin\ExportController::class, 'bundle'])->name('exports.bundle');
    Route::get('exports/lara-shop-classeur-complet.xlsx', [Admin\ExportController::class, 'workbook'])->name('exports.workbook');
    // One dataset in one format: /admin/exports/commandes.csv, .xlsx or .pdf (+ the list filters)
    Route::get('exports/{dataset}.{format}', [Admin\ExportController::class, 'download'])
        ->whereIn('format', Admin\ExportController::FORMATS)->name('exports.download');
});
