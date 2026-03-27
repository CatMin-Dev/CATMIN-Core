@extends('admin.layouts.catmin')

@section('page_title', 'Utilisateurs')

@section('content')
<x-admin.crud.page-header
    title="Utilisateurs"
    subtitle="Gestion des comptes dashboard et de leurs roles associes."
>
    <a class="btn btn-primary" href="{{ admin_route('users.create') }}">
        <i class="bi bi-person-plus me-1"></i>Nouveau compte
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <x-admin.crud.table-card
        title="Comptes utilisateurs"
        :count="$users->count()"
        :empty-colspan="$supportsActivation ? 6 : 5"
        empty-message="Aucun utilisateur."
    >
        <x-slot:head>
                    <tr>
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
                            <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('users.edit', ['user' => $user->id]) }}">
                                <i class="bi bi-pencil-square me-1"></i>Modifier
                            </a>
                            @if($supportsActivation)
                                <form method="post" action="{{ admin_route('users.toggle_active', ['user' => $user->id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit">
                                        <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
                                        {{ $user->is_active ? 'Desactiver' : 'Activer' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
