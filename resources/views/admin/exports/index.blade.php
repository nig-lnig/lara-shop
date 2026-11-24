@extends('layouts.admin')

@section('title', 'Exports')

@section('content')
    <div class="page-head">
        <div>
            <h1>Exports</h1>
            <p class="muted">
                Trois formats : <strong>CSV</strong> (UTF-8, séparateur « ; »), <strong>Excel</strong> (.xlsx mis en forme, montants en nombres)
                et <strong>PDF</strong> (rapport imprimable). Aucun mot de passe ni jeton n’est exporté.
            </p>
        </div>
    </div>

    {{-- The complete export: visually distinct from the single-file exports --}}
    <section class="card bundle-card spaced">
        <div>
            <h2>Export complet de la boutique</h2>
            <p>Les {{ count($datasets) }} tables, enregistrements archivés compris.</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.exports.workbook') }}" class="btn"><x-icon name="download" /> Classeur Excel complet ({{ count($datasets) }} onglets)</a>
            <a href="{{ route('admin.exports.bundle') }}" class="btn"><x-icon name="download" /> Archive CSV complète (ZIP)</a>
        </div>
    </section>

    <section class="card">
        <div class="section-head">
            <h2>Une table à la fois</h2>
        </div>
        <p class="muted small">Toutes les lignes, archivées comprises. Pour un export filtré, utilisez « Exporter ces résultats » au-dessus du tableau de la liste concernée.</p>
        <ul class="download-list">
            @foreach ($datasets as $key => [$baseName, $label])
                <li>
                    <div>
                        <strong>{{ $label }}</strong>
                        <span class="file">{{ $baseName }}.csv · .xlsx · .pdf</span>
                    </div>
                    <x-export-formats route="admin.exports.download" :dataset="$key" :query="['archived' => 'all']" />
                </li>
            @endforeach
        </ul>
    </section>
@endsection
