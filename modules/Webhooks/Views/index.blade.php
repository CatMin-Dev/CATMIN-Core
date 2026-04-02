@extends('admin.layouts.catmin')

@section('page_title', 'Webhooks')

@section('content')
<x-admin.crud.page-header
    title="Webhooks sortants"
    subtitle="Envoyer des notifications HTTP signées HMAC-SHA256 vers des services tiers."
>
    @if(catmin_can('module.webhooks.create'))
    <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nouveau webhook
    </a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <form id="bulk-form" method="post" action="{{ admin_route('webhooks.bulk') }}" class="{{(!catmin_can('module.webhooks.edit') && !catmin_can('module.webhooks.delete')) ? 'd-none' : ''}}">
        @csrf

        <x-admin.crud.table-card title="Webhooks enregistrés ({{ $webhooks->total() }})">
            <x-slot:head>
                <tr>
                    @if(catmin_can('module.webhooks.edit') || catmin_can('module.webhooks.delete'))
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input">
                        </th>
                    @endif
                    <th>Nom</th>
                    <th>URL</th>
                    <th>Événements</th>
                    <th>Statut</th>
                    <th>Dernier resultat</th>
                    <th>Dernière exéc.</th>
                    <th></th>
                </tr>
            </x-slot:head>

            <x-slot:rows>
                @foreach($webhooks as $webhook)
                    <tr>
                        @if(catmin_can('module.webhooks.edit') || catmin_can('module.webhooks.delete'))
                            <td>
                                <input type="checkbox" name="bulk_select[]" value="{{ $webhook->id }}" class="form-check-input bulk-checkbox">
                            </td>
                        @endif
                        <td class="fw-semibold">{{ $webhook->name }}</td>
                        <td class="small text-muted" style="max-width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="{{ $webhook->url }}">
                            {{ $webhook->url }}
                        </td>
                        <td>
                            @foreach($webhook->events ?? [] as $event)
                                <span class="badge bg-light text-dark border me-1">{{ $event }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($webhook->status === 'active')
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($webhook->last_delivery_status === null)
                                <span class="text-muted">—</span>
                            @elseif((int) $webhook->last_delivery_status >= 200 && (int) $webhook->last_delivery_status < 300)
                                <span class="badge bg-success">{{ $webhook->last_delivery_status }}</span>
                            @elseif((int) $webhook->last_delivery_status === 0)
                                <span class="badge bg-danger">Erreur transport</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ $webhook->last_delivery_status }}</span>
                            @endif
                            @if(!empty($webhook->last_delivery_error))
                                <div class="text-danger text-truncate" style="max-width:220px;" title="{{ $webhook->last_delivery_error }}">
                                    {{ $webhook->last_delivery_error }}
                                </div>
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap">
                            {{ $webhook->last_delivery_at?->format('d/m/Y H:i') ?? $webhook->last_triggered_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="text-end text-nowrap">
                            @if(catmin_can('module.webhooks.edit'))
                            <a href="{{ route('admin.webhooks.edit', $webhook->id) }}"
                               class="btn btn-sm btn-outline-secondary me-1" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(catmin_can('module.webhooks.delete'))
                            <form class="d-inline" method="POST" action="{{ route('admin.webhooks.destroy', $webhook->id) }}"
                                  onsubmit="return confirm('Supprimer le webhook « {{ addslashes($webhook->name) }} » ?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-slot:rows>
        </x-admin.crud.table-card>

        @if((catmin_can('module.webhooks.edit') || catmin_can('module.webhooks.delete')) && $webhooks->count() > 0)
            <div class="bulk-actions-toolbar mt-3" id="bulk-toolbar" style="display: none; gap: 1rem; align-items: center;">
                <span class="selected-count">
                    <span id="selected-count-value">0</span> sélectionné(s)
                </span>
                
                <div class="bulk-action-buttons">
                    @if(catmin_can('module.webhooks.edit'))
                        <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="activate" data-requires-confirmation="false">
                            <i class="bi bi-play-circle me-1"></i>Activer
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" data-bulk-action="deactivate" data-requires-confirmation="false">
                            <i class="bi bi-pause-circle me-1"></i>Desactiver
                        </button>
                    @endif
                    @if(catmin_can('module.webhooks.delete'))
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-action="delete" data-requires-confirmation="true" data-confirmation-message="Supprimer les webhooks selectionnés ?">
                            <i class="bi bi-trash me-1"></i>Supprimer
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </form>

    @if($webhooks->hasPages())
    <div class="mt-3">{{ $webhooks->links() }}</div>
    @endif

    {{-- Incoming endpoint info --}}
    <div class="card mt-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-arrow-down-circle me-1"></i>Endpoint entrant</h2></div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Pour recevoir des webhooks, envoyez un <code>POST</code> vers l'URL ci-dessous.<br>
                Définissez <code>CATMIN_WEBHOOK_INCOMING_TOKEN</code> dans votre <code>.env</code> pour sécuriser l'accès.
            </p>
            <pre class="bg-light rounded p-3 small mb-0">POST {{ url('/webhooks/incoming/{votre-token}') }}</pre>
        </div>
    </div>
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
        if (toolbar) toolbar.style.display = checkedCount > 0 ? 'flex' : 'none';
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
                alert('Veuillez sélectionner au moins un webhook');
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
