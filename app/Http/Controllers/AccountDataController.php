<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use App\Support\CsvExport;
use App\Support\ExportDatasets;
use App\Support\PdfExport;
use App\Support\XlsxExport;
use Illuminate\Http\Request;

/**
 * "Mes données": the signed-in customer downloads everything the shop stores
 * about them, as CSV, Excel or PDF. Every query below is limited to THEIR
 * client / user id.
 */
class AccountDataController extends Controller
{
    /** Dataset key => [file name without extension, French label]. */
    public const DATASETS = [
        'profil' => ['mes-donnees-profil', 'Profil et adresse'],
        'commandes' => ['mes-donnees-commandes', 'Commandes'],
        'lignes-commande' => ['mes-donnees-lignes-commande', 'Lignes de commande'],
        'favoris' => ['mes-donnees-favoris', 'Produits favoris'],
    ];

    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    public function index(Request $request)
    {
        $user = $request->user();

        return view('my-data.index', [
            'datasets' => self::DATASETS,
            'client' => $user->client,
            'ordersCount' => Order::where('client_id', $user->client_id)->count(),
            'favoritesCount' => $user->favoriteProducts()->count(),
        ]);
    }

    /** One file: /mes-donnees/commandes.xlsx */
    public function download(Request $request, string $dataset, string $format)
    {
        abort_unless(isset(self::DATASETS[$dataset]) && in_array($format, self::FORMATS, true), 404);

        [$baseName, $label] = self::DATASETS[$dataset];
        $fileName = $baseName.'.'.$format;
        $data = $this->dataset($request->user(), $dataset);
        $subtitle = 'Client '.$request->user()->client->code;

        return match ($format) {
            'csv' => CsvExport::download($fileName, $data),
            'xlsx' => XlsxExport::download($fileName, $label, $data, $subtitle),
            'pdf' => PdfExport::report($fileName, $label, [$label => $data], $subtitle),
        };
    }

    /** Everything as CSV files in one ZIP archive. */
    public function bundle(Request $request)
    {
        $files = [];
        foreach (self::DATASETS as $key => [$baseName]) {
            $files[$baseName.'.csv'] = $this->dataset($request->user(), $key);
        }

        return CsvExport::zip('mes-donnees-lara-shop.zip', $files);
    }

    /** Everything in one Excel workbook (one tab per dataset). */
    public function workbook(Request $request)
    {
        return XlsxExport::workbook('mes-donnees-lara-shop.xlsx', $this->allSections($request->user()), 'Client '.$request->user()->client->code);
    }

    /** Everything in one PDF report. */
    public function report(Request $request)
    {
        $user = $request->user();

        return PdfExport::report('mes-donnees-lara-shop.pdf', 'Mes données', $this->allSections($user), 'Client '.$user->client->code.' · '.$user->name);
    }

    private function allSections(User $user): array
    {
        $sections = [];
        foreach (self::DATASETS as $key => [, $label]) {
            $sections[$label] = $this->dataset($user, $key);
        }

        return $sections;
    }

    /** Builds one dataset, always scoped to the given user. */
    private function dataset(User $user, string $key): array
    {
        return match ($key) {
            'profil' => ExportDatasets::profile($user),
            'favoris' => ExportDatasets::favorites(ExportDatasets::favoritesQuery($user->id)),
            'commandes' => ExportDatasets::orders(Order::where('client_id', $user->client_id)),
            'lignes-commande' => ExportDatasets::orderLines(
                OrderLine::whereHas('order', fn ($q) => $q->where('client_id', $user->client_id))
            ),
        };
    }
}
