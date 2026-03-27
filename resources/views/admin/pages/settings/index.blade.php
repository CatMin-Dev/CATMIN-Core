@extends('admin.layouts.catmin')

@section('page_title', 'Parametres')

@section('content')
<header class="catmin-page-header">
    <h1 class="h3 mb-1">Parametres</h1>
    <p class="text-muted mb-0">Inventaire des settings centralises.</p>
</header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Settings</h2>
            <span class="badge text-bg-light">{{ $settings->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead><tr><th>Groupe</th><th>Cle</th><th>Valeur</th><th>Type</th><th>Public</th></tr></thead>
                <tbody>
                    @forelse($settings as $setting)
                        <tr>
                            <td>{{ $setting->group ?: 'general' }}</td>
                            <td>{{ $setting->key }}</td>
                            <td>{{ is_scalar($setting->value) ? $setting->value : json_encode($setting->value) }}</td>
                            <td>{{ $setting->type ?: 'string' }}</td>
                            <td><span class="badge {{ $setting->is_public ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $setting->is_public ? 'Oui' : 'Non' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun parametre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
