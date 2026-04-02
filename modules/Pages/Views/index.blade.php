@extends('admin.layouts.catmin')

@section('page_title', 'Pages')

@section('content')
<x-admin.crud.page-header
    title="Pages"
    subtitle="Gestion des pages simples du frontend CATMIN."
>
    <x-admin.crud.search-form
        :action-url="admin_route('pages.manage')"
        :query="$search ?? ''"
        placeholder="Recherche pages..."
    />

    @if(catmin_can('module.pages.create'))
        <a class="btn btn-primary" href="{{ admin_route('pages.create') }}">
            <i class="bi bi-plus-circle me-1"></i>Nouvelle page
        </a>
    @endif

    <div class="btn-group" role="group" aria-label="Filtre pages">
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'active' ? 'active' : '' }}" href="{{ admin_route('pages.manage', ['scope' => 'active', 'q' => $search ?? '']) }}">Actives</a>
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'all' ? 'active' : '' }}" href="{{ admin_route('pages.manage', ['scope' => 'all', 'q' => $search ?? '']) }}">Toutes</a>
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'trash' ? 'active' : '' }}" href="{{ admin_route('pages.manage', ['scope' => 'trash', 'q' => $search ?? '']) }}">Corbeille ({{ (int) ($trashedCount ?? 0) }})</a>
    </div>

    @if(($scope ?? 'active') === 'trash' && catmin_can('module.pages.trash'))
        <form method="post" action="{{ admin_route('pages.trash.empty') }}" onsubmit="return confirm('Vider toute la corbeille ? Suppression definitive irreversible.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger" type="submit">
                <i class="bi bi-trash3 me-1"></i>Vider la corbeille
            </button>
        </form>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <form id="bulk-form" method="post" action="{{ admin_route('pages.bulk') }}" class="{{!catmin_can('module.pages.edit') && !catmin_can('module.pages.trash') ? 'd-none' : ''}}">
        @csrf

        <x-admin.crud.table-card
            title="Pages publiees et brouillons"
            :count="$pages->total()"
            :empty-colspan="8"
            empty-message="Aucune page pour le moment."
        >
            <x-slot:head>
                <tr>
                    @if(catmin_can('module.pages.edit') || catmin_can('module.pages.trash'))
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input" data-action="select-all">
                        </th>
                    @endif
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Slug</th>
                    <th>Statut</th>
                    <th>Publication</th>
                    <th>Maj</th>
                    <th class="text-end">Actions</th>
                </tr>
            </x-slot:head>

            <x-slot:rows>
                @foreach($pages as $page)
                    <tr>
                        @if(catmin_can('module.pages.edit') || catmin_can('module.pages.trash'))
                            <td>
                                <input type="checkbox" name="bulk_select[]" value="{{ $page->id }}" class="form-check-input bulk-checkbox">
                            </td>
                        @endif
                        <td>{{ $page->id }}</td>
                        <td>{{ $page->title }}</td>
                        <td>{{ $page->slug }}</td>
                        <td>
                            @if(method_exists($page, 'trashed') && $page->trashed())
                                <span class="badge text-bg-danger">Supprimee</span>
                            @else
                                <span class="badge {{ $page->status === 'published' ? 'text-bg-success' : ($page->status === 'scheduled' ? 'text-bg-warning' : 'text-bg-secondary') }}">
                                    {{ $page->status === 'published' ? 'Publie' : ($page->status === 'scheduled' ? 'Programme' : 'Brouillon') }}
                                </span>
                            @endif
                        </td>
                        <td>{{ optional($page->published_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                        <td>{{ optional($page->updated_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                @if(catmin_can('module.pages.edit') || catmin_can('module.pages.trash'))
                                    @if(method_exists($page, 'trashed') && $page->trashed())
                                        @if(catmin_can('module.pages.trash'))
                                            <form method="post" action="{{ admin_route('pages.restore', ['page' => $page->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-success" type="submit">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restaurer
                                                </button>
                                            </form>
                                            <form method="post" action="{{ admin_route('pages.force_delete', ['page' => $page->id]) }}" onsubmit="return confirm('Supprimer définitivement cette page ? Action irreversible.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="bi bi-trash3 me-1"></i>Hard delete
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @if(catmin_can('module.pages.edit'))
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('pages.edit', ['page' => $page->id]) }}">
                                                <i class="bi bi-pencil-square me-1"></i>Modifier
                                            </a>
                                            <form method="post" action="{{ admin_route('pages.toggle_status', ['page' => $page->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm {{ in_array($page->status, ['published', 'scheduled'], true) ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                                    <i class="bi {{ in_array($page->status, ['published', 'scheduled'], true) ? 'bi-pause-circle' : 'bi-check2-circle' }} me-1"></i>
                                                    {{ in_array($page->status, ['published', 'scheduled'], true) ? 'Depublier' : 'Publier' }}
                                                </button>
                                            </form>
                                        @endif
                                        @if(catmin_can('module.pages.trash'))
                                            <form method="post" action="{{ admin_route('pages.destroy', ['page' => $page->id]) }}" onsubmit="return confirm('Deplacer cette page dans la corbeille ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="bi bi-trash me-1"></i>Soft delete
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-slot:rows>
        </x-admin.crud.table-card>

        @if((catmin_can('module.pages.edit') || catmin_can('module.pages.trash')) && ($pages->count() > 0))
            <div class="bulk-actions-toolbar mt-3" id="bulk-toolbar" style="display: none; gap: 1rem; align-items: center;">
                <span class="selected-count">
                    <span id="selected-count-value">0</span> sélectionné(s)
                </span>
                
                <div class="bulk-action-buttons">
                    @if(catmin_can('module.pages.edit'))
                        <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="publish" data-requires-confirmation="false">
                            <i class="bi bi-check-circle me-1"></i>Publier
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" data-bulk-action="unpublish" data-requires-confirmation="false">
                            <i class="bi bi-pause-circle me-1"></i>Depublier
                        </button>
                    @endif
                    @if(catmin_can('module.pages.trash'))
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-action="trash" data-requires-confirmation="true" data-confirmation-message="Envoyer {{ $pages->count() }} page(s) à la corbeille ?">
                            <i class="bi bi-trash me-1"></i>Envoyer en corbeille
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </form>

    @if($pages->hasPages())
        <div class="mt-3">
            <x-admin.crud.pagination :paginator="$pages" />
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const toolbar = document.getElementById('bulk-toolbar');
    const countValue = document.getElementById('selected-count-value');
    const bulkForm = document.getElementById('bulk-form');
    
    function updateToolbarVisibility() {
        const checkedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
        countValue.textContent = checkedCount;
        toolbar.style.display = checkedCount > 0 ? 'flex' : 'none';
    }
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('input[name="bulk_select[]"]').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateToolbarVisibility();
        });
    }
    
    document.querySelectorAll('input[name="bulk_select[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateToolbarVisibility();
            if (selectAllCheckbox) {
                const totalCount = document.querySelectorAll('input[name="bulk_select[]"]').length;
                const checkedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
                selectAllCheckbox.checked = (checkedCount === totalCount && checkedCount > 0);
            }
        });
    });
    
    document.querySelectorAll('[data-bulk-action]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const action = this.dataset.bulkAction;
            const requiresConfirmation = this.dataset.requiresConfirmation === 'true';
            const message = this.dataset.confirmationMessage || 'Êtes-vous sûr ?';
            
            const selectedCount = document.querySelectorAll('input[name="bulk_select[]"]:checked').length;
            
            if (selectedCount === 0) {
                alert('Veuillez sélectionner au moins une page');
                return;
            }
            
            if (requiresConfirmation && !confirm(message)) {
                return;
            }
            
            // Set the action and submit the form
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'bulk_action';
            actionInput.value = action;
            bulkForm.appendChild(actionInput);
            bulkForm.submit();
        });
    });
});
</script>

<style>
.bulk-actions-toolbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.selected-count {
    font-weight: 600;
    color: #495057;
}

.bulk-action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
</style>
@endsection
