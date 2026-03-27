@extends('admin.layouts.catmin')

@section('page_title', 'Roles')

@section('content')
<x-admin.crud.page-header
    title="Roles"
    subtitle="Etat des roles et de leur usage dans le module Users."
/>

<div class="catmin-page-body">
    <x-admin.crud.table-card
        title="Roles definis"
        :count="$roles->count()"
        :empty-colspan="6"
        empty-message="Aucun role."
    >
        <x-slot:head>
                    <tr>
                        <th>Nom</th>
                        <th>Affichage</th>
                        <th>Priorite</th>
                        <th>Utilisateurs</th>
                        <th>Systeme</th>
                        <th>Actif</th>
                    </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->display_name ?: 'n/a' }}</td>
                    <td>{{ $role->priority }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td><span class="badge {{ $role->is_system ? 'text-bg-warning' : 'text-bg-secondary' }}">{{ $role->is_system ? 'Oui' : 'Non' }}</span></td>
                    <td><span class="badge {{ $role->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $role->is_active ? 'Oui' : 'Non' }}</span></td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
