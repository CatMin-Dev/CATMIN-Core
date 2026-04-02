@extends('admin.layouts.catmin')

@section('page_title', 'Utilisateurs')

@section('content')
<x-admin.crud.page-header
    title="Utilisateurs"
    subtitle="Gestion des comptes dashboard et de leurs roles associes."
>
    @if(catmin_can('module.users.create'))
        <a class="btn btn-primary" href="{{ admin_route('users.create') }}">
            <i class="bi bi-person-plus me-1"></i>Nouveau compte
        </a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <form id="bulk-form" method="post" action="{{ admin_route('users.bulk') }}" class="{{!catmin_can('module.users.config') ? 'd-none' : ''}}">
        @csrf

        <x-admin.crud.table-card
            title="Comptes utilisateurs"
            :count="$users->count()"
            :empty-colspan="($supportsActivation ? 6 : 5) + (catmin_can('module.users.config') ? 1 : 0)"
            empty-message="Aucun utilisateur."
        >
            <x-slot:head>
                <tr>
                    @if(catmin_can('module.users.config'))
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" class="form-check-input">
                        </th>
                    @endif
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Roles</th>
                    @if($supportsActivation)
                        <th>Actif</th>
                    @endif
                    <th class="text-end">Actions</th>
                </tr>
            </x-slot:head>

            <x-slot:rows>
                @foreach($users as $user)
                    <tr>
                        @if(catmin_can('module.users.config'))
                            <td>
                                <input type="checkbox" name="bulk_select[]" value="{{ $user->id }}" class="form-check-input bulk-checkbox">
                            </td>
                        @endif
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('display_name')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', ') ?: 'Aucun role' }}</td>
                        @if($supportsActivation)
                            <td>
                                <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $user->is_active ? 'Oui' : 'Non' }}
                                </span>
                            </td>
                        @endif
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                @if(catmin_can('module.users.edit'))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('users.edit', ['user' => $user->id]) }}">
                                        <i class="bi bi-pencil-square me-1"></i>Modifier
                                    </a>
                                @endif
                                @if($supportsActivation && catmin_can('module.users.config'))
                                    <form method="post" action="{{ admin_route('users.toggle_active', ['user' => $user->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                            <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
                                            {{ $user->is_active ? 'Desactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                @endif
                                @if(catmin_can('module.users.delete'))
                                    <form method="post" action="{{ admin_route('users.destroy', ['user' => $user->id]) }}"
                                          onsubmit="return confirm('Supprimer l\'utilisateur {{ addslashes($user->name) }} ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash me-1"></i>Supprimer
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-slot:rows>
        </x-admin.crud.table-card>

        @if(catmin_can('module.users.config') && $users->count() > 0)
            <div class="bulk-actions-toolbar mt-3" id="bulk-toolbar" style="display: none; gap: 1rem; align-items: center;">
                <span class="selected-count">
                    <span id="selected-count-value">0</span> sélectionné(s)
                </span>
                
                <div class="bulk-action-buttons">
                    <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="activate" data-requires-confirmation="false">
                        <i class="bi bi-play-circle me-1"></i>Activer
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bulk-action="deactivate" data-requires-confirmation="false">
                        <i class="bi bi-pause-circle me-1"></i>Desactiver
                    </button>
                </div>
            </div>
        @endif
    </form>
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
                alert('Veuillez sélectionner au moins un utilisateur');
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
