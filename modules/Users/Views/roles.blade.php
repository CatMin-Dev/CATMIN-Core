@extends('admin.layouts.catmin')

@section('page_title', 'Roles')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Roles</h1>
    <p class="text-muted mb-0">Etat des roles et de leur usage dans le module Users.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Roles definis</h2>
            <span class="badge text-bg-light">{{ $roles->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Affichage</th>
                        <th>Priorite</th>
                        <th>Utilisateurs</th>
                        <th>Systeme</th>
                        <th>Actif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->display_name ?: 'n/a' }}</td>
                            <td>{{ $role->priority }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td><span class="badge {{ $role->is_system ? 'text-bg-warning' : 'text-bg-secondary' }}">{{ $role->is_system ? 'Oui' : 'Non' }}</span></td>
                            <td><span class="badge {{ $role->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $role->is_active ? 'Oui' : 'Non' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
