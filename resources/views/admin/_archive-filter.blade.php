{{-- "Actifs / Archivés / Tous" select, shared by the clients, suppliers and products lists. --}}
<div class="field">
    <label for="archived">Affichage</label>
    <select id="archived" name="archived">
        <option value="">Actifs</option>
        <option value="archived" @selected(($filters['archived'] ?? '') === 'archived')>Archivés</option>
        <option value="all" @selected(($filters['archived'] ?? '') === 'all')>Tous</option>
    </select>
</div>
