{{--
    Three download buttons for one dataset: CSV · Excel · PDF.
    Usage: <x-export-formats route="admin.exports.download" dataset="commandes" :query="request()->except('page')" label="Exporter ces résultats" />
    $query = extra URL parameters (the list filters), so the file contains exactly the rows on screen.
--}}
@props(['route', 'dataset', 'query' => [], 'label' => null])

<div {{ $attributes->class('export-formats') }} role="group" aria-label="{{ $label ?? 'Télécharger' }}">
    @if ($label)
        <span class="export-label">{{ $label }}</span>
    @endif
    @foreach (['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $text)
        <a class="btn btn-outline btn-sm" href="{{ route($route, ['dataset' => $dataset, 'format' => $format] + $query) }}"
           @if ($label) aria-label="{{ $label }} en {{ $text }}" @endif>
            <x-icon name="download" /> {{ $text }}
        </a>
    @endforeach
</div>
