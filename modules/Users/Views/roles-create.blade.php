@extends('admin.layouts.catmin')

@section('page_title', 'Nouveau role')

@section('content')
<x-admin.crud.page-header
    title="Creer un role"
    subtitle="Definit un ensemble de permissions reutilisables pour les utilisateurs."
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('roles.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour liste
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <form method="post" action="{{ admin_route('roles.store') }}" class="row g-4">
        @csrf

        {{-- Infos de base --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Informations du role</h2>
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">
                            Identifiant <span class="text-danger">*</span>
                            <small class="text-muted ms-1">(lettres, chiffres, tirets)</small>
                        </label>
                        <input id="name" name="name" type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required pattern="[a-zA-Z0-9\-_]+"
                               placeholder="ex: content-manager">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="display_name">Nom d'affichage</label>
                        <input id="display_name" name="display_name" type="text"
                               class="form-control @error('display_name') is-invalid @enderror"
                               value="{{ old('display_name') }}" placeholder="ex: Gestionnaire de contenu">
                        @error('display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Decrit le perimetre de ce role...">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="priority">Priorite</label>
                        <input id="priority" name="priority" type="number" min="0" max="1000"
                               class="form-control @error('priority') is-invalid @enderror"
                               value="{{ old('priority', 0) }}">
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Plus la valeur est faible, plus le role est prioritaire.</div>
                    </div>

                    <div class="col-6 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active"
                                   name="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">Role actif</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Permissions</h2>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPermissions(true)">
                            <i class="bi bi-check-all me-1"></i>Tout cocher
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPermissions(false)">
                            <i class="bi bi-x-lg me-1"></i>Tout decocher
                        </button>
                    </div>
                </div>
                <div class="card-body" style="max-height:480px;overflow-y:auto;">
                    @foreach($allPermissions as $module => $permissions)
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="text-uppercase text-muted small mb-0 fw-semibold">{{ $module }}</h6>
                                <a href="#" class="small text-muted" onclick="toggleModulePermissions('{{ $module }}', event)">
                                    tout le module
                                </a>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($permissions as $permission)
                                    @php($action = explode('.', $permission)[2] ?? $permission)
                                    <div class="form-check form-check-inline me-0">
                                        <input class="form-check-input perm-checkbox perm-module-{{ $module }}"
                                               type="checkbox"
                                               id="perm_{{ str_replace('.', '_', $permission) }}"
                                               name="permissions[]"
                                               value="{{ $permission }}"
                                               @checked(collect(old('permissions', []))->contains($permission))>
                                        <label class="form-check-label small" for="perm_{{ str_replace('.', '_', $permission) }}">
                                            {{ $action }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Creer le role
            </button>
            <a class="btn btn-outline-secondary" href="{{ admin_route('roles.manage') }}">Annuler</a>
        </div>
    </form>
</div>

<script>
function toggleAllPermissions(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
}
function toggleModulePermissions(module, e) {
    e.preventDefault();
    const boxes = document.querySelectorAll('.perm-module-' + module);
    const allChecked = Array.from(boxes).every(cb => cb.checked);
    boxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endsection
