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
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Nom</th><th>Slug</th><th>Version</th><th>Type</th><th>Etat</th><th>Dependances</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($modules as $module)
                        @php
                            $isSystemModule = in_array($module->slug, ['core']);
                        @endphp
                        <tr>
                            <td>{{ $module->name }}</td>
                            <td>{{ $module->slug }}</td>
                            <td>{{ $module->version ?? 'n/a' }}</td>
                            <td>
                                @if($isSystemModule)
                                    <span class="badge text-bg-dark">Système</span>
                                @else
                                    <span class="badge text-bg-light text-dark">Optionnel</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $module->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $module->enabled ? 'Actif' : 'Desactive' }}</span></td>
                            <td>{{ collect($module->depends ?? [])->join(', ') ?: 'Aucune' }}</td>
                            <td>
                                @if(!$isSystemModule)
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr?');">
                                        @csrf
                                        @if($module->enabled)
                                            <button type="submit" formaction="{{ route('admin.modules.disable', $module->slug) }}" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-power"></i> Desactiver
                                            </button>
                                        @else
                                            <button type="submit" formaction="{{ route('admin.modules.enable', $module->slug) }}" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activer
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun module.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mt-3" role="alert">
            <strong>Erreur:</strong>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
</div>

@if(session('success'))
    <div class="mt-3">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.catmin-page-body').insertAdjacentElement('beforebegin', alert);
            });
        </script>
    </div>
@endif

@if(session('error'))
    <div class="mt-3">
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.setAttribute('role', 'alert');
                alert.innerHTML = `
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.catmin-page-body').insertAdjacentElement('beforebegin', alert);
            });
        </script>
    </div>
@endif
@endsection
