{{--
    PDF report: one table per section. Used by the admin exports and by "Mes données".
    $title, $subtitle, $sections = [section title => dataset] (see App\Support\ExportDatasets)
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    @include('pdf._styles')
</head>
<body>
<table class="doc-header">
    <tr>
        <td><table class="brand"><tr><td class="brand-mark">L</td><td class="brand-name">{{ config('shop.name') }}</td></tr></table></td>
        <td class="doc-title">
            <h1>{{ $title }}</h1>
            <div class="muted small">Généré le {{ now()->format('d/m/Y à H:i') }}@if ($subtitle) · {{ $subtitle }}@endif</div>
        </td>
    </tr>
</table>

@foreach ($sections as $sectionTitle => $dataset)
    @if (count($sections) > 1)
        <h2>{{ $sectionTitle }}</h2>
    @endif
    <p class="section-meta muted small">{{ count($dataset['rows']) }} ligne(s)</p>

    @if (count($dataset['rows']) === 0)
        <div class="empty">Aucune donnée.</div>
    @else
        <table @class(['data', 'dense' => count($dataset['headings']) >= 11])>
            <thead>
            <tr>
                @foreach ($dataset['headings'] as $i => $heading)
                    <th @class(['num' => in_array($i, array_merge($dataset['money'] ?? [], $dataset['integer'] ?? []), true)])>{{ $heading }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($dataset['rows'] as $row)
                <tr>
                    @foreach (array_values($row) as $i => $value)
                        <td class="{{ App\Support\PdfExport::cellClass($dataset, $i, $value) }}">{{ App\Support\PdfExport::cell($dataset, $i, $value) }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endforeach
</body>
</html>
