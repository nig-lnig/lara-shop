<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Support\CsvExport;
use App\Support\ExportDatasets;
use App\Support\PdfExport;
use App\Support\XlsxExport;
use Illuminate\Http\Request;

/**
 * Whole-store exports (admins only, see the "admin" middleware in routes/web.php).
 * Formats: CSV, Excel (.xlsx) and PDF. Each export accepts the same URL filters
 * as its admin list page, so "Exporter ces résultats" gives exactly the rows on screen.
 */
class ExportController extends Controller
{
    /** Dataset key => [file name without extension, French label]. */
    public const DATASETS = [
        'clients' => ['clients', 'Clients'],
        'fournisseurs' => ['fournisseurs', 'Fournisseurs'],
        'produits' => ['produits-et-stock', 'Produits et stock'],
        'commandes' => ['commandes', 'Commandes'],
        'lignes-commande' => ['lignes-commande', 'Lignes de commande'],
        'favoris' => ['favoris', 'Favoris des clients'],
        'comptes' => ['comptes-utilisateurs', 'Comptes utilisateurs'],
    ];

    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    public function index()
    {
        return view('admin.exports.index', ['datasets' => self::DATASETS]);
    }

    /** One dataset in one format: /admin/exports/commandes.xlsx?status=pending */
    public function download(Request $request, string $dataset, string $format)
    {
        abort_unless(isset(self::DATASETS[$dataset]) && in_array($format, self::FORMATS, true), 404);

        [$baseName, $label] = self::DATASETS[$dataset];
        $filtersText = $this->describeFilters($request);
        $fileName = $baseName.($filtersText ? '-filtre-'.now()->format('Ymd-His') : '').'.'.$format;
        $data = $this->dataset($request, $dataset);

        return match ($format) {
            'csv' => CsvExport::download($fileName, $data),
            'xlsx' => XlsxExport::download($fileName, $label, $data, $filtersText),
            'pdf' => PdfExport::report($fileName, $label, [$label => $data], $filtersText),
        };
    }

    /** All datasets, complete (archived records included): one ZIP of CSV files. */
    public function bundle()
    {
        $all = new Request(['archived' => 'all']);
        $files = [];
        foreach (self::DATASETS as $key => [$baseName]) {
            $files[$baseName.'.csv'] = $this->dataset($all, $key);
        }

        return CsvExport::zip('lara-shop-export-complet-'.now()->format('Ymd-His').'.zip', $files);
    }

    /** All datasets, complete: one Excel workbook with one tab per dataset. */
    public function workbook()
    {
        $all = new Request(['archived' => 'all']);
        $sheets = [];
        foreach (self::DATASETS as $key => [, $label]) {
            $sheets[$label] = $this->dataset($all, $key);
        }

        return XlsxExport::workbook('lara-shop-classeur-complet-'.now()->format('Ymd-His').'.xlsx', $sheets, 'Export complet');
    }

    private function dataset(Request $request, string $key): array
    {
        return match ($key) {
            'clients' => ExportDatasets::clients(
                Client::filter($f = $this->filters($request, ClientController::FILTERS))->archiveState($f['archived'] ?? null)
            ),
            'fournisseurs' => ExportDatasets::suppliers(
                Supplier::filter($f = $this->filters($request, SupplierController::FILTERS))->archiveState($f['archived'] ?? null)
            ),
            'produits' => ExportDatasets::products(
                Product::filter($f = $this->filters($request, ProductController::FILTERS))->archiveState($f['archived'] ?? null)
            ),
            'commandes' => ExportDatasets::orders(
                Order::filter($this->filters($request, OrderController::FILTERS))
            ),
            'lignes-commande' => ExportDatasets::orderLines(
                OrderLine::whereHas('order', fn ($q) => $q->filter($this->filters($request, OrderController::FILTERS)))
            ),
            'favoris' => ExportDatasets::favorites(ExportDatasets::favoritesQuery()),
            'comptes' => ExportDatasets::accounts(User::query()),
        };
    }

    /** "Statut : En attente · Recherche : « cahier »" — printed under the title of Excel/PDF files. */
    private function describeFilters(Request $request): string
    {
        $labels = [
            'q' => fn ($v) => 'Recherche : « '.$v.' »',
            'status' => fn ($v) => 'Statut : '.(Order::STATUSES[$v] ?? $v),
            'payment_terms' => fn ($v) => 'Paiement : '.(Order::PAYMENT_TERMS[$v] ?? $v),
            'from' => fn ($v) => 'Du '.$v,
            'to' => fn ($v) => 'Au '.$v,
            'type' => fn ($v) => 'Type : '.(Client::TYPES[$v] ?? $v),
            'family' => fn ($v) => 'Famille : '.(Product::FAMILIES[$v] ?? $v),
            'supplier_id' => fn ($v) => 'Fournisseur : '.(Supplier::find($v)?->name ?? $v),
            'stock' => fn ($v) => $v === 'low' ? 'Stock faible' : '',
            'visibility' => fn ($v) => $v === 'hidden' ? 'Produits masqués' : 'Produits visibles',
            'archived' => fn ($v) => match ($v) { 'archived' => 'Archivés', 'all' => '', default => '' },
        ];

        return collect($this->filters($request, array_keys($labels)))
            ->map(fn ($value, $key) => $labels[$key]($value))
            ->filter()
            ->implode(' · ');
    }
}
