@extends('admin.layouts.catmin')

@section('page_title', 'Parametres')

@section('content')
<x-admin.crud.page-header
    title="Parametres"
    subtitle="Configuration essentielle du site et des options generales."
/>

<div class="catmin-page-body d-grid gap-4">
    <x-admin.crud.flash-messages />

    <div class="card">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Parametres essentiels</h2>
        </div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('settings.update') }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="site_name">Nom du site</label>
                    <input id="site_name" name="site_name" type="text" class="form-control @error('site_name') is-invalid @enderror" value="{{ old('site_name', $essentials['site_name']) }}" required>
                    @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="site_url">URL du site</label>
                    <input id="site_url" name="site_url" type="url" class="form-control @error('site_url') is-invalid @enderror" value="{{ old('site_url', $essentials['site_url']) }}" required>
                    @error('site_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="admin_path">Chemin admin</label>
                    <input id="admin_path" name="admin_path" type="text" class="form-control @error('admin_path') is-invalid @enderror" value="{{ old('admin_path', $essentials['admin_path']) }}" required>
                    <div class="form-text">Valeur stockee pour la configuration admin future (sans slash initial).</div>
                    @error('admin_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="admin_theme">Theme admin</label>
                    <input id="admin_theme" name="admin_theme" type="text" class="form-control @error('admin_theme') is-invalid @enderror" value="{{ old('admin_theme', $essentials['admin_theme']) }}" required>
                    @error('admin_theme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="site_frontend_enabled" name="site_frontend_enabled" value="1" @checked(old('site_frontend_enabled', $essentials['site_frontend_enabled']))>
                        <label class="form-check-label" for="site_frontend_enabled">Frontend public actif</label>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="registration_open" name="registration_open" value="1" @checked(old('registration_open', $essentials['registration_open']))>
                        <label class="form-check-label" for="registration_open">Inscriptions publiques autorisees</label>
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1"></i>Enregistrer les parametres
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-admin.crud.table-card
        title="Valeurs stockees"
        :count="$trackedSettings->count()"
        :empty-colspan="5"
        empty-message="Aucune valeur enregistree."
    >
        <x-slot:head>
                    <tr>
                        <th>Groupe</th>
                        <th>Cle</th>
                        <th>Valeur</th>
                        <th>Type</th>
                        <th>Public</th>
                    </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($trackedSettings as $setting)
                <tr>
                    <td>{{ $setting->group ?: 'general' }}</td>
                    <td>{{ $setting->key }}</td>
                    <td>{{ is_scalar($setting->value) ? $setting->value : json_encode($setting->value) }}</td>
                    <td>{{ $setting->type ?: 'string' }}</td>
                    <td><span class="badge {{ $setting->is_public ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $setting->is_public ? 'Oui' : 'Non' }}</span></td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
