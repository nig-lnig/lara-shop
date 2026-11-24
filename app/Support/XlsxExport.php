<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Styled Excel files (.xlsx) built with PhpSpreadsheet, from the same datasets
 * as the CSV exports (see ExportDatasets).
 *
 * Layout of each sheet:
 *   row 1  title          "Lara-Shop · Commandes"
 *   row 2  subtitle       export date, number of rows, filters
 *   row 4  column titles  ink-blue band, frozen, with filter arrows
 *   row 5+ data           zebra rows, money as real numbers formatted "1 234,50 DH"
 *
 * Safety: every text value is written with setCellValueExplicit(..., TYPE_STRING),
 * so a value starting with "=" is stored as text and is never run as a formula.
 */
class XlsxExport
{
    private const INK = '14213D';

    private const PAPER = 'F6F4EE';

    private const LINE = 'E5E1D8';

    private const FIRST_DATA_ROW = 5;

    /** One dataset in one sheet. */
    public static function download(string $filename, string $title, array $dataset, string $subtitle = ''): StreamedResponse
    {
        return self::workbook($filename, [$title => $dataset], $subtitle);
    }

    /**
     * Several datasets in one workbook: one sheet (tab) per dataset.
     *
     * @param  array<string, array>  $sheets  sheet title => dataset
     */
    public static function workbook(string $filename, array $sheets, string $subtitle = ''): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(config('shop.name'))
            ->setTitle(config('shop.name').' · '.implode(', ', array_keys($sheets)));
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $title => $dataset) {
            $sheet = $spreadsheet->createSheet();
            self::fillSheet($sheet, $title, $dataset, $subtitle);
        }
        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private static function fillSheet(Worksheet $sheet, string $title, array $dataset, string $subtitle): void
    {
        // Sheet names: max 31 characters, no \ / ? * [ ] :
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', '', $title), 0, 31));

        $headings = $dataset['headings'];
        $money = $dataset['money'] ?? [];
        $integer = $dataset['integer'] ?? [];
        $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
        $moneyFormat = '#,##0.00 "'.config('shop.currency').'"';

        // --- Data rows ------------------------------------------------------
        $rowNumber = self::FIRST_DATA_ROW;
        foreach ($dataset['rows'] as $row) {
            foreach (array_values($row) as $i => $value) {
                $cell = Coordinate::stringFromColumnIndex($i + 1).$rowNumber;

                if ($value === null || $value === '') {
                    continue;
                } elseif (in_array($i, $money, true)) {
                    $sheet->setCellValueExplicit($cell, (float) $value, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($moneyFormat);
                } elseif (in_array($i, $integer, true)) {
                    $sheet->setCellValueExplicit($cell, (int) $value, DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                }
            }
            if (($rowNumber - self::FIRST_DATA_ROW) % 2 === 1) { // zebra rows
                $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::PAPER);
            }
            $rowNumber++;
        }
        $lastRow = max(self::FIRST_DATA_ROW, $rowNumber - 1);
        $count = $rowNumber - self::FIRST_DATA_ROW;

        // --- Title and subtitle -------------------------------------------------
        $sheet->setCellValueExplicit('A1', config('shop.name').' · '.$title, DataType::TYPE_STRING);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::INK);
        $sheet->setCellValueExplicit('A2', trim('Exporté le '.now()->format('d/m/Y à H:i').' · '.$count.' ligne(s) '.($subtitle ? '· '.$subtitle : '')), DataType::TYPE_STRING);
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('5E6880');
        $sheet->getRowDimension(1)->setRowHeight(24);

        // --- Column titles --------------------------------------------------------
        $headerRow = self::FIRST_DATA_ROW - 1;
        foreach ($headings as $i => $heading) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1).$headerRow, $heading, DataType::TYPE_STRING);
        }
        $header = $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}");
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::INK);
        $header->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($headerRow)->setRowHeight(30);

        // Numbers aligned right (titles too)
        foreach (array_merge($money, $integer) as $i) {
            $column = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getStyle("{$column}{$headerRow}:{$column}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Thin dividers between rows
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->getBorders()->getHorizontal()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::LINE);
        $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::LINE);

        // Column widths from the content (between 10 and 45 characters)
        foreach ($headings as $i => $heading) {
            $column = Coordinate::stringFromColumnIndex($i + 1);
            $width = mb_strlen($heading);
            for ($r = self::FIRST_DATA_ROW; $r <= min($lastRow, self::FIRST_DATA_ROW + 300); $r++) {
                $width = max($width, mb_strlen((string) $sheet->getCell($column.$r)->getFormattedValue()));
            }
            $sheet->getColumnDimension($column)->setWidth(min(45, max(10, $width + 3)));
        }

        // Frozen titles, filter arrows, printing on one page width in landscape
        $sheet->freezePane('A'.self::FIRST_DATA_ROW);
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $sheet->setSelectedCell('A'.self::FIRST_DATA_ROW);
    }
}
