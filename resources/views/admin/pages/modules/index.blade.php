@extends('admin.layouts.catmin')

@section('page_title', 'Modules')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Modules</h1>
    <p class="text-muted mb-0">Catalogue des modules declares.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Etat des modules</h2>
            <span class="badge text-bg-light">{{ $modules->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Version</th><th>Etat</th><th>Dependances</th></tr></thead>
                <tbody>
                    @forelse($modules as $module)
                        <tr>
                            <td>{{ $module->name }}</td>
                            <td>{{ $module->slug }}</td>
                            <td>{{ $module->version ?? 'n/a' }}</td>
                            <td><span class="badge {{ $module->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $module->enabled ? 'Actif' : 'Desactive' }}</span></td>
                            <td>{{ collect($module->depends ?? [])->join(', ') ?: 'Aucune' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun module.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
