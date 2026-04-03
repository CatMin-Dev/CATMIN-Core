@extends('admin.layouts.catmin')

@section('page_title', 'Profil admin')

@section('content')
<x-admin.crud.page-header
    title="Profil admin"
    subtitle="Informations personnelles, avatar, mot de passe et securite de session."
/>

<div class="catmin-page-body d-grid gap-4">
    <x-admin.crud.flash-messages />

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Informations de profil</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.profile.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="first_name">Prenom</label>
                            <input id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $adminUser->first_name) }}">
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="last_name">Nom</label>
                            <input id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $adminUser->last_name) }}">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="contact_email">Email de contact</label>
                            <input id="contact_email" name="contact_email" type="email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', $adminUser->contact_email) }}">
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="phone">Telephone</label>
                            <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $adminUser->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Compte principal (lecture seule)</label>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <input class="form-control" value="{{ $adminUser->username }}" disabled>
                                </div>
                                <div class="col-12 col-md-6">
                                    <input class="form-control" value="{{ $adminUser->email }}" disabled>
                                </div>
                            </div>
                            <div class="form-text">Le login principal reste separe des informations de contact.</div>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Enregistrer le profil</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Avatar</h2></div>
                <div class="card-body">
                    @if($canUseMediaPicker)
                        <form method="POST" action="{{ route('admin.profile.avatar') }}" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <x-admin.media.picker-field
                                    inputName="avatar_media_asset_id"
                                    inputId="avatar_media_asset_id"
                                    label="Avatar"
                                    :value="$adminUser->avatar_media_asset_id"
                                    helpText="Choisissez un media depuis la bibliotheque pour definir votre avatar."
                                />
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit">Mettre a jour l avatar</button>
                                <button class="btn btn-outline-secondary" type="submit" name="avatar_media_asset_id" value="">Retirer l avatar</button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0">Le module Media n est pas disponible, le picker avatar est indisponible.</div>
                    @endif
                </div>
            </div>

            @if(!empty($profileExtensionEnabled) && \Illuminate\Support\Facades\Route::has('admin.profile.extensions.update'))
                @include('addon_catmin_profile_extensions::profile-extended-card', [
                    'profileExtension' => $profileExtension ?? [],
                ])
            @endif

            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Changer le mot de passe</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.profile.password') }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <label class="form-label" for="current_password">Mot de passe actuel</label>
                            <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_password">Nouveau mot de passe</label>
                            <input id="new_password" name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" required>
                            @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="new_password_confirmation">Confirmation</label>
                            <input id="new_password_confirmation" name="new_password_confirmation" type="password" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-outline-danger" type="submit">Changer le mot de passe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Securite rapide</h2></div>
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('admin.2fa.setup') }}">Gerer 2FA</a>
                    <a class="btn btn-outline-primary" href="{{ route('admin.sessions.index') }}">Voir les sessions</a>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.settings.manage') }}#tab-security">Parametres securite</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Etat 2FA</h2></div>
                <div class="card-body">
                    <span class="badge {{ $adminUser->two_factor_enabled ? 'text-bg-success' : 'text-bg-warning' }}">
                        {{ $adminUser->two_factor_enabled ? '2FA activee' : '2FA inactive' }}
                    </span>
                    <div class="small text-muted mt-2">Compte: {{ $adminUser->is_super_admin ? 'Super-admin protege' : 'Admin' }}</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h2 class="h6 mb-0">{{ __('users.locale_label') }}</h2></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.profile.locale') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="locale">{{ __('users.locale_label') }}</label>
                            <select id="locale" name="locale" class="form-select">
                                @foreach(\App\Services\LocaleService::localeOptions() as $code => $label)
                                    <option value="{{ $code }}" {{ $adminUser->getLocale() === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">{{ __('core.save') }}</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Infos session utiles</h2></div>
                <div class="card-body small">
                    @php($current = collect($sessions)->firstWhere('session_id', $currentSessionId))
                    <div><strong>Sessions actives:</strong> {{ count($sessions) }}</div>
                    <div><strong>Derniere connexion:</strong> {{ $adminUser->last_login_at ? $adminUser->last_login_at->format('d/m/Y H:i') : 'n/a' }}</div>
                    <div><strong>IP courante:</strong> {{ $current['ip_address'] ?? request()->ip() }}</div>
                    <div><strong>User agent:</strong></div>
                    <div class="text-muted">{{ \Illuminate\Support\Str::limit((string) ($current['user_agent'] ?? request()->userAgent()), 120) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canUseMediaPicker)
    <x-admin.media.picker-modal />
@endif
@endsection
