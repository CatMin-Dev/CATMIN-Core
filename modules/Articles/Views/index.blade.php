@extends('admin.layouts.catmin')

@section('page_title', 'Articles')

@section('content')
<x-admin.crud.page-header
    title="Articles"
    subtitle="Module unifie pour les contenus editoriaux et actualites."
>
    <x-admin.crud.search-form
        :action-url="admin_route('articles.manage')"
        :query="$search ?? ''"
        placeholder="Recherche articles..."
    />

    @if(catmin_can('module.articles.create'))
        <a class="btn btn-primary" href="{{ admin_route('articles.create') }}">Nouvel article</a>
    @endif

    @if(catmin_can('module.articles.config'))
        <a class="btn btn-outline-secondary" href="{{ admin_route('articles.categories.index') }}">Catégories</a>
        <a class="btn btn-outline-secondary" href="{{ admin_route('articles.tags.index') }}">Tags</a>
    @endif

    <div class="btn-group" role="group" aria-label="Filtre articles">
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'active' ? 'active' : '' }}" href="{{ admin_route('articles.manage', ['scope' => 'active', 'q' => $search ?? '']) }}">Actifs</a>
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'all' ? 'active' : '' }}" href="{{ admin_route('articles.manage', ['scope' => 'all', 'q' => $search ?? '']) }}">Tous</a>
        <a class="btn btn-outline-secondary {{ ($scope ?? 'active') === 'trash' ? 'active' : '' }}" href="{{ admin_route('articles.manage', ['scope' => 'trash', 'q' => $search ?? '']) }}">Corbeille ({{ (int) ($trashedCount ?? 0) }})</a>
    </div>

    @if(($scope ?? 'active') === 'trash' && catmin_can('module.articles.trash'))
        <form method="post" action="{{ admin_route('articles.trash.empty') }}" onsubmit="return confirm('Vider toute la corbeille articles ? Suppression definitive irreversible.');">
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

    <form method="get" action="{{ admin_route('articles.manage') }}" class="row g-3 mb-3">
        <input type="hidden" name="scope" value="{{ $scope ?? 'active' }}">
        <div class="col-12 col-lg-4">
            <label class="form-label" for="category_id">Catégorie</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">Toutes</option>
                @foreach(($categories ?? collect()) as $category)
                    <option value="{{ $category['id'] }}" @selected((string) ($selectedCategoryId ?? '') === (string) $category['id'])>{{ $category['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-lg-4">
            <label class="form-label" for="tag_id">Tag</label>
            <select id="tag_id" name="tag_id" class="form-select">
                <option value="">Tous</option>
                @foreach(($tags ?? collect()) as $tag)
                    <option value="{{ $tag->id }}" @selected((string) ($selectedTagId ?? '') === (string) $tag->id)>{{ $tag->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-lg-4 d-flex align-items-end gap-2">
            <button class="btn btn-outline-primary" type="submit">Filtrer</button>
            <a class="btn btn-outline-secondary" href="{{ admin_route('articles.manage', ['scope' => $scope ?? 'active', 'q' => $search ?? '']) }}">Reset</a>
        </div>
    </form>

    <form id="bulk-form" method="post" action="{{ admin_route('articles.bulk') }}" class="{{!catmin_can('module.articles.edit') && !catmin_can('module.articles.trash') ? 'd-none' : ''}}">
        @csrf

        <x-admin.crud.table-card
            title="Articles"
            :count="$items->total()"
            :empty-colspan="11"
            empty-message="Aucun article."
        >
            <x-slot:head>
                <tr>
                    @if(catmin_can('module.articles.edit') || catmin_can('module.articles.trash'))
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input" data-action="select-all">
                        </th>
                    @endif
                    <th>ID</th>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Slug</th>
                    <th>Extrait</th>
                    <th>Taxonomie</th>
                    <th>Statut</th>
                    <th>Publication</th>
                    <th>Media/SEO</th>
                    <th class="text-end">Actions</th>
                </tr>
            </x-slot:head>

            <x-slot:rows>
                @foreach($items as $item)
                    <tr>
                        @if(catmin_can('module.articles.edit') || catmin_can('module.articles.trash'))
                            <td>
                                <input type="checkbox" name="bulk_select[]" value="{{ $item->id }}" class="form-check-input bulk-checkbox">
                            </td>
                        @endif
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->title }}</td>
                        <td><span class="badge {{ $item->content_type === 'news' ? 'text-bg-info' : 'text-bg-primary' }}">{{ $item->content_type === 'news' ? 'News' : 'Article' }}</span></td>
                        <td>{{ $item->slug }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($item->excerpt ?: '', 90) ?: 'n/a' }}</td>
                        <td>
                            <div class="small">
                                <div><strong>Cat:</strong> {{ $item->category?->name ?: '—' }}</div>
                                <div><strong>Tags:</strong> {{ $item->tags->pluck('name')->join(', ') ?: '—' }}</div>
                            </div>
                        </td>
                        <td>
                            @if(method_exists($item, 'trashed') && $item->trashed())
                                <span class="badge text-bg-danger">Supprime</span>
                            @else
                                <span class="badge {{ $item->status === 'published' ? 'text-bg-success' : ($item->status === 'scheduled' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $item->status === 'published' ? 'Publie' : ($item->status === 'scheduled' ? 'Programme' : 'Brouillon') }}</span>
                            @endif
                        </td>
                        <td>{{ optional($item->published_at)->format('d/m/Y H:i') ?: 'n/a' }}</td>
                        <td>M{{ $item->media_asset_id ?: '-' }} / S{{ $item->seo_meta_id ?: '-' }}</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                @if(catmin_can('module.articles.edit') || catmin_can('module.articles.trash'))
                                    @if(method_exists($item, 'trashed') && $item->trashed())
                                        @if(catmin_can('module.articles.trash'))
                                            <form method="post" action="{{ admin_route('articles.restore', ['article' => $item->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-success" type="submit">Restaurer</button>
                                            </form>
                                            <form method="post" action="{{ admin_route('articles.force_delete', ['article' => $item->id]) }}" onsubmit="return confirm('Supprimer définitivement cet article ? Action irreversible.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Hard delete</button>
                                            </form>
                                        @endif
                                    @else
                                        @if(catmin_can('module.articles.edit'))
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('articles.edit', ['article' => $item->id]) }}">Modifier</a>
                                            <form method="post" action="{{ admin_route('articles.toggle_status', ['article' => $item->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm {{ in_array($item->status, ['published', 'scheduled'], true) ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                                    {{ in_array($item->status, ['published', 'scheduled'], true) ? 'Depublier' : 'Publier' }}
                                                </button>
                                            </form>
                                        @endif
                                        @if(catmin_can('module.articles.trash'))
                                            <form method="post" action="{{ admin_route('articles.destroy', ['article' => $item->id]) }}" onsubmit="return confirm('Deplacer cet article dans la corbeille ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Soft delete</button>
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

        @if((catmin_can('module.articles.edit') || catmin_can('module.articles.trash')) && ($items->count() > 0))
            <div class="bulk-actions-toolbar mt-3" id="bulk-toolbar" style="display: none; gap: 1rem; align-items: center;">
                <span class="selected-count">
                    <span id="selected-count-value">0</span> sélectionné(s)
                </span>
                
                <div class="bulk-action-buttons">
                    @if(catmin_can('module.articles.edit'))
                        <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="publish" data-requires-confirmation="false">
                            <i class="bi bi-check-circle me-1"></i>Publier
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" data-bulk-action="unpublish" data-requires-confirmation="false">
                            <i class="bi bi-pause-circle me-1"></i>Depublier
                        </button>
                    @endif
                    @if(catmin_can('module.articles.trash'))
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-action="trash" data-requires-confirmation="true" data-confirmation-message="Envoyer les articles selectionnés à la corbeille ?">
                            <i class="bi bi-trash me-1"></i>Envoyer en corbeille
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </form>

    @if($items->hasPages())
        <div class="mt-3">
            <x-admin.crud.pagination :paginator="$items" />
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
                alert('Veuillez sélectionner au moins un article');
                return;
            }
            
            if (requiresConfirmation && !confirm(message)) {
                return;
            }
            
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
