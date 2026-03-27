@extends('admin.layouts.catmin')

@section('page_title', 'Utilisateurs')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Utilisateurs</h1>
    <p class="text-muted mb-0">Liste des comptes presents en base.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Comptes</h2>
            <span class="badge text-bg-light">{{ $users->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Roles</th><th>Inscrit le</th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->roles->pluck('display_name')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', ') ?: 'Aucun role' }}</td>
                            <td>{{ optional($user->created_at)->format('d/m/Y H:i') ?? 'n/a' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
