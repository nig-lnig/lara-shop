<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Defines the columns of every export (CSV, Excel and PDF use the same definitions).
 *
 * Each method receives a query (already filtered / scoped by the controller) and returns:
 *   'headings' => column titles
 *   'rows'     => lazy collection of rows (arrays of values)
 *   'money'    => indexes of money columns   (values are decimal strings like "12.50")
 *   'integer'  => indexes of whole-number columns
 * Each export format then decides how to display money (12,50 / 12.50 DH / number cell).
 *
 * The SAME definitions serve the admin exports and the customer "Mes données"
 * exports; only the query changes (whole store vs. own data).
 * Passwords, password hashes, remember tokens, checkout tokens and sessions
 * are never selected here.
 */
class ExportDatasets
{
    private static function yesNo(bool $value): string
    {
        return $value ? 'Oui' : 'Non';
    }

    public static function clients(Builder $query): array
    {
        return [
            'headings' => ['Code client', 'Nom', 'Rue', 'Code postal', 'Ville', 'Type (code)', 'Type',
                'Remise spéciale (%)', 'E-mail du compte', 'Nombre de commandes', 'Archivé le', 'Créé le'],
            'money' => [],
            'integer' => [7, 9],
            'rows' => $query->with('user:id,client_id,email')->withCount('orders')->orderBy('id')->lazy()
                ->map(fn (Client $c) => [
                    $c->code, $c->name, $c->street, $c->postal_code, $c->city, $c->type, $c->typeLabel(),
                    $c->special_discount, $c->user?->email, $c->orders_count,
                    fr_date($c->archived_at), fr_date($c->created_at),
                ]),
        ];
    }

    public static function suppliers(Builder $query): array
    {
        return [
            'headings' => ['Code fournisseur', 'Nom', 'Ville', 'Nombre de produits', 'Archivé le', 'Créé le'],
            'money' => [],
            'integer' => [3],
            'rows' => $query->withCount('products')->orderBy('id')->lazy()
                ->map(fn (Supplier $s) => [
                    $s->code, $s->name, $s->city, $s->products_count, fr_date($s->archived_at), fr_date($s->created_at),
                ]),
        ];
    }

    /** Products and inventory. */
    public static function products(Builder $query): array
    {
        return [
            'headings' => ['Code produit', 'Désignation', 'Famille (code)', 'Famille', 'Prix unitaire',
                'Stock', 'Seuil d’alerte', 'Stock faible', 'Visible en boutique', 'Code fournisseur', 'Fournisseur', 'Archivé le', 'Créé le'],
            'money' => [4],
            'integer' => [5, 6],
            'rows' => $query->with('supplier:id,code,name')->orderBy('id')->lazy()
                ->map(fn (Product $p) => [
                    $p->code, $p->designation, $p->family, $p->familyLabel(), $p->unit_price,
                    $p->stock, $p->low_stock_threshold, self::yesNo($p->isLowStock()), self::yesNo($p->is_listed),
                    $p->supplier?->code, $p->supplier?->name, fr_date($p->archived_at), fr_date($p->created_at),
                ]),
        ];
    }

    public static function orders(Builder $query): array
    {
        return [
            'headings' => ['N° commande', 'Date commande', 'Code client', 'Client', 'Condition (code)',
                'Condition de paiement', 'Statut', 'Nombre de lignes', 'Sous-total', 'Remise (%)',
                'Montant remise', 'Total', 'Stock restitué le'],
            'money' => [8, 10, 11],
            'integer' => [7, 9],
            'rows' => $query->with('client:id,code,name')->withCount('lines')->orderBy('id')->lazy()
                ->map(fn (Order $o) => [
                    $o->number, fr_date($o->order_date), $o->client?->code, $o->client?->name,
                    $o->payment_terms, $o->paymentTermsLabel(), $o->statusLabel(), $o->lines_count,
                    $o->subtotal, $o->discount_rate, $o->discount_amount, $o->total, fr_date($o->stock_restored_at),
                ]),
        ];
    }

    public static function orderLines(Builder $query): array
    {
        return [
            'headings' => ['N° commande', 'Date commande', 'Code client', 'Statut commande', 'Code produit',
                'Désignation', 'Quantité', 'Prix unitaire', 'Total ligne'],
            'money' => [7, 8],
            'integer' => [6],
            'rows' => $query->with(['order:id,number,order_date,client_id,status', 'order.client:id,code', 'product:id,code,designation'])
                ->orderBy('order_id')->orderBy('id')->lazy()
                ->map(fn (OrderLine $l) => [
                    $l->order->number, fr_date($l->order->order_date), $l->order->client?->code,
                    $l->order->statusLabel(), $l->product->code, $l->product->designation, $l->quantity,
                    $l->unit_price, $l->line_total,
                ]),
        ];
    }

    /** $query is a query on the "favorites" table (see favoritesQuery()). */
    public static function favorites($query): array
    {
        return [
            'headings' => ['Code client', 'Client', 'E-mail du compte', 'Code produit', 'Désignation', 'Prix unitaire actuel', 'Ajouté le'],
            'money' => [5],
            'integer' => [],
            'rows' => $query
                ->join('users', 'users.id', '=', 'favorites.user_id')
                ->leftJoin('clients', 'clients.id', '=', 'users.client_id')
                ->join('products', 'products.id', '=', 'favorites.product_id')
                ->select('clients.code as client_code', 'clients.name as client_name', 'users.email',
                    'products.code as product_code', 'products.designation', 'products.unit_price', 'favorites.created_at')
                ->orderBy('favorites.id')->lazy(500)
                ->map(fn ($f) => [
                    $f->client_code, $f->client_name, $f->email, $f->product_code, $f->designation,
                    number_format((float) $f->unit_price, 2, '.', ''), $f->created_at ? fr_date(Carbon::parse($f->created_at)) : '',
                ]),
        ];
    }

    /** Login accounts, without any authentication secret. */
    public static function accounts(Builder $query): array
    {
        return [
            'headings' => ['Nom', 'E-mail', 'Rôle', 'Code client', 'Créé le'],
            'money' => [],
            'integer' => [],
            'rows' => $query->with('client:id,code')->orderBy('id')->lazy()
                ->map(fn (User $u) => [
                    $u->name, $u->email, $u->isAdmin() ? 'Administrateur' : 'Client', $u->client?->code, fr_date($u->created_at),
                ]),
        ];
    }

    /** The signed-in customer's own profile (one row). */
    public static function profile(User $user): array
    {
        $c = $user->client;

        return [
            'headings' => ['Code client', 'Nom du compte', 'E-mail', 'Nom client', 'Rue', 'Code postal', 'Ville',
                'Type (code)', 'Type', 'Remise spéciale (%)', 'Compte créé le'],
            'money' => [],
            'integer' => [9],
            'rows' => [[
                $c->code, $user->name, $user->email, $c->name, $c->street, $c->postal_code, $c->city,
                $c->type, $c->typeLabel(), $c->special_discount, fr_date($user->created_at),
            ]],
        ];
    }

    /** Favorites query helper (whole store or one user). */
    public static function favoritesQuery(?int $userId = null)
    {
        return DB::table('favorites')->when($userId, fn ($q) => $q->where('favorites.user_id', $userId));
    }
}
