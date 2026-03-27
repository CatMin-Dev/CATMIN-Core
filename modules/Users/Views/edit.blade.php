@extends('admin.layouts.catmin')

@section('page_title', 'Edition utilisateur')

@section('content')
<x-admin.crud.page-header
    title="Modifier un utilisateur"
    subtitle="Mets a jour les infos du compte et ses roles."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('users.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour liste
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Compte #{{ $user->id }}</h2>
        </div>
        <div class="card-body">
            <form method="post" action="{{ admin_route('users.update', ['user' => $user->id]) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="name">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="password">Nouveau mot de passe (optionnel)</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="password_confirmation">Confirmation</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label d-block">Roles</label>
                    <div class="d-flex flex-wrap gap-3">
                        @php($selectedRoles = collect(old('roles', $user->roles->pluck('id')->all()))->map(fn ($id) => (int) $id))
                        @forelse($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="role_{{ $role->id }}" name="roles[]" value="{{ $role->id }}" @checked($selectedRoles->contains($role->id))>
                                <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->display_name ?: $role->name }}</label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Aucun role actif disponible.</p>
                        @endforelse
                    </div>
                </div>

                @if($supportsActivation)
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', (bool) $user->is_active))>
                            <label class="form-check-label" for="is_active">Compte actif</label>
                        </div>
                    </div>
                @endif

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1"></i>Enregistrer
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('users.manage') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
