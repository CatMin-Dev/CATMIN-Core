@extends('admin.layouts.catmin')

@section('page_title', 'Roles')

@section('content')
<x-admin.crud.page-header
    title="Roles"
    subtitle="Gestion des roles et de leurs permissions dans CATMIN."
>
    @if(catmin_can('module.users.config'))
        <a class="btn btn-primary" href="{{ admin_route('roles.create') }}">
            <i class="bi bi-shield-plus me-1"></i>Nouveau role
        </a>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Roles definis"
        :count="$roles->count()"
        :empty-colspan="7"
        empty-message="Aucun role. Lancez <code>php artisan catmin:rbac:sync</code> pour creer les roles systeme."
    >
        <x-slot:head>
            <tr>
                <th>Nom</th>
                <th>Affichage</th>
                <th>Priorite</th>
                <th>Permissions</th>
                <th>Utilisateurs</th>
                <th>Systeme</th>
                <th>Actif</th>
                @if(catmin_can('module.users.config'))
                    <th class="text-end">Actions</th>
                @endif
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($roles as $role)
                <tr>
                    <td><code>{{ $role->name }}</code></td>
                    <td>{{ $role->display_name ?: '—' }}</td>
                    <td>{{ $role->priority }}</td>
                    <td>
                        @php($perms = $role->permissions ?? [])
                        @if(in_array('*', $perms))
                            <span class="badge text-bg-danger">Toutes (*)</span>
                        @else
                            <span class="badge text-bg-secondary">{{ count($perms) }}</span>
                        @endif
                    </td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        <span class="badge {{ $role->is_system ? 'text-bg-warning' : 'text-bg-secondary' }}">
                            {{ $role->is_system ? 'Oui' : 'Non' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $role->is_active ? 'text-bg-success' : 'text-bg-danger' }}">
                            {{ $role->is_active ? 'Oui' : 'Non' }}
                        </span>
                    </td>
                    @if(catmin_can('module.users.config'))
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('roles.edit', ['role' => $role->id]) }}">
                                    <i class="bi bi-pencil-square me-1"></i>Modifier
                                </a>
                                @if(!$role->is_system)
                                    <form method="post" action="{{ admin_route('roles.destroy', ['role' => $role->id]) }}"
                                          onsubmit="return confirm('Supprimer le role {{ addslashes($role->display_name ?: $role->name) }} ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash me-1"></i>Supprimer
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
