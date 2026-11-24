{{--
    Edit / archive-or-delete / restore buttons for one row.
    Needs: $item (model), $routePrefix (e.g. 'admin.clients'), $label (e.g. 'le client Cli-000001')
--}}
<div class="row-actions">
    @if ($item->isArchived())
        <form action="{{ route($routePrefix.'.restore', $item) }}" method="post">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-outline btn-sm">Restaurer</button>
        </form>
    @else
        <a href="{{ route($routePrefix.'.edit', $item) }}" class="btn btn-outline btn-sm">Modifier</a>
        <form action="{{ route($routePrefix.'.destroy', $item) }}" method="post"
              data-confirm="Supprimer {{ $label }} ? S’il possède un historique, il sera archivé au lieu d’être supprimé.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger-ghost btn-sm">Supprimer</button>
        </form>
    @endif
</div>
