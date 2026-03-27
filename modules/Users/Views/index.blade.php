@extends('admin.layouts.catmin')

@section('page_title', 'Utilisateurs')

@section('content')
<header class="catmin-page-header d-flex flex-wrap gap-3 justify-content-between align-items-start">
    <div>
        <h1 class="h3 mb-1">Utilisateurs</h1>
        <p class="text-muted mb-0">Gestion des comptes dashboard et de leurs roles associes.</p>
    </div>
    <a class="btn btn-primary" href="{{ admin_route('users.create') }}">
        <i class="bi bi-person-plus me-1"></i>Nouveau compte
    </a>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Comptes utilisateurs</h2>
            <span class="badge text-bg-light">{{ $users->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
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
                </thead>
                <tbody>
                    @forelse($users as $user)
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
                    @empty
                        <tr>
                            <td colspan="{{ $supportsActivation ? 6 : 5 }}" class="text-center text-muted py-4">Aucun utilisateur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
