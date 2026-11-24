<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * PDF documents made with dompdf from Blade views (resources/views/pdf).
 *
 *  - report(): one or several datasets as tables (admin exports, "Mes données")
 *  - document(): any Blade view (the order summary)
 * Every page gets a footer: shop name, date and "Page 2 / 5".
 */
class PdfExport
{
    /**
     * @param  array<string, array>  $sections  section title => dataset (see ExportDatasets)
     */
    public static function report(string $filename, string $title, array $sections, string $subtitle = '', string $orientation = 'landscape'): Response
    {
        // Rows are read once here, so the view can count them.
        foreach ($sections as $name => $dataset) {
            $sections[$name]['rows'] = collect($dataset['rows'])->all();
        }

        return self::document('pdf.report', compact('title', 'sections', 'subtitle'), $filename, $orientation);
    }

    public static function document(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation)
            ->setOption(['defaultFont' => 'DejaVu Sans', 'isFontSubsettingEnabled' => true]);

        // Footer on every page (drawn after the layout, when the page count is known).
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $width = $canvas->get_width();
        $height = $canvas->get_height();
        $color = [0.37, 0.41, 0.5];
        $canvas->page_text(36, $height - 28, config('shop.name').' · '.now()->format('d/m/Y H:i'), $font, 7.5, $color);
        $canvas->page_text($width - 90, $height - 28, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 7.5, $color);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * CSS class of one cell: numbers aligned right; business codes (Com-000001)
     * and dates (24/11/2026 10:30) never broken over two lines.
     */
    public static function cellClass(array $dataset, int $index, mixed $value): string
    {
        if (in_array($index, array_merge($dataset['money'] ?? [], $dataset['integer'] ?? []), true)) {
            return 'num';
        }

        return preg_match('/^([A-Z][a-z]{1,2}-\d{6}|\d{2}\/\d{2}\/\d{4}( \d{2}:\d{2})?)$/', (string) $value) ? 'nowrap' : '';
    }

    /** Formats one cell for the PDF: money with currency, the rest as text. */
    public static function cell(array $dataset, int $index, mixed $value): string
    {
        if (in_array($index, $dataset['money'] ?? [], true) && $value !== null && $value !== '') {
            return money($value);
        }

        return (string) ($value ?? '');
    }
}
