{{--
    Bar above an admin table: number of results + the export of exactly these results.
    Needs: $paginator, $noun (e.g. 'client(s)'), $dataset (export key), optional $extraExports.
    The export links reuse the filters currently in the URL (request()->except('page')).
--}}
@php($isFiltered = collect(request()->except(['page']))->filter(fn ($v) => is_string($v) && $v !== '')->isNotEmpty())

<div class="table-toolbar">
    <p><strong>{{ $paginator->total() }}</strong> {{ $noun }} {{ $isFiltered ? 'correspondant aux filtres' : '' }}</p>
    <div class="toolbar-exports">
        <x-export-formats route="admin.exports.download" :dataset="$dataset" :query="request()->except('page')"
                          :label="$isFiltered ? 'Exporter ces résultats' : 'Exporter la liste'" />
        @foreach (($extraExports ?? []) as $key => $label)
            <x-export-formats route="admin.exports.download" :dataset="$key" :query="request()->except('page')" :label="$label" />
        @endforeach
    </div>
</div>
