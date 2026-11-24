<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Writes CSV files that open correctly in Excel / LibreOffice with French text.
 *
 *  - UTF-8 with a BOM (3 invisible bytes) so Excel detects the accents.
 *  - ";" as separator, because French Excel uses "," for decimals.
 *  - Money columns are written with a decimal comma: 12,50
 *  - Formula injection protection: a cell that starts with = + - @ (or a tab /
 *    carriage return) is prefixed with an apostrophe, so a spreadsheet shows
 *    it as text instead of running it as a formula.
 */
class CsvExport
{
    public const DELIMITER = ';';

    private const BOM = "\xEF\xBB\xBF";

    /** Makes one cell safe for spreadsheets. */
    public static function safeCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /** Writes the heading row and all data rows of a dataset into an open file handle. */
    public static function write($handle, array $dataset): void
    {
        $money = $dataset['money'] ?? [];

        fwrite($handle, self::BOM);
        fputcsv($handle, $dataset['headings'], self::DELIMITER, '"', '');

        foreach ($dataset['rows'] as $row) {
            foreach ($money as $index) {
                $row[$index] = self::number($row[$index]);
            }
            fputcsv($handle, array_map([self::class, 'safeCell'], $row), self::DELIMITER, '"', '');
        }
    }

    /** One CSV file sent to the browser as a download. */
    public static function download(string $filename, array $dataset): StreamedResponse
    {
        return response()->streamDownload(function () use ($dataset) {
            $out = fopen('php://output', 'w');
            self::write($out, $dataset);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Several CSV files in one ZIP archive.
     *
     * @param  array<string, array>  $datasets  file name => dataset
     */
    public static function zip(string $zipName, array $datasets): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'larashop');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($datasets as $fileName => $dataset) {
            $buffer = fopen('php://temp', 'w+');
            self::write($buffer, $dataset);
            rewind($buffer);
            $zip->addFromString($fileName, stream_get_contents($buffer));
            fclose($buffer);
        }

        $zip->close();

        return response()->download($path, $zipName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /** "12.50" -> "12,50" (French decimal comma). */
    public static function number(string|int|float|null $amount): string
    {
        return $amount === null || $amount === '' ? '' : str_replace('.', ',', (string) $amount);
    }
}
