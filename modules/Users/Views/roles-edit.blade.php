@extends('admin.layouts.catmin')

@section('page_title', 'Modifier le role')

@section('content')
<x-admin.crud.page-header
    title="Role : {{ $role->display_name ?: $role->name }}"
    subtitle="{{ $role->is_system ? 'Role systeme — nom et statut systeme non modifiables.' : 'Role personnalise — entierement modifiable.' }}"
>
    <a class="btn btn-outline-secondary" href="{{ admin_route('roles.manage') }}">
        <i class="bi bi-arrow-left me-1"></i>Retour liste
    </a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    @if($role->is_system)
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-shield-lock me-2"></i>
            Ce role est un <strong>role systeme</strong>.
            Son identifiant <code>{{ $role->name }}</code> ne peut pas etre modifie.
            @if(in_array('*', $role->permissions ?? []))
                Ses permissions wildcard (<code>*</code>) sont egalement proteges.
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="post" action="{{ admin_route('roles.update', ['role' => $role->id]) }}" class="row g-4">
        @csrf
        @method('PUT')

        {{-- Infos de base --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Informations du role</h2>
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name_display">Identifiant</label>
                        <input id="name_display" type="text" class="form-control bg-light"
                               value="{{ $role->name }}" disabled>
                        <div class="form-text">L'identifiant ne peut pas etre modifie.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="display_name">Nom d'affichage</label>
                        <input id="display_name" name="display_name" type="text"
                               class="form-control @error('display_name') is-invalid @enderror"
                               value="{{ old('display_name', $role->display_name) }}"
                               placeholder="ex: Gestionnaire de contenu">
                        @error('display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Decrit le perimetre de ce role...">{{ old('description', $role->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-6">
                        <label class="form-label" for="priority">Priorite</label>
                        <input id="priority" name="priority" type="number" min="0" max="1000"
                               class="form-control @error('priority') is-invalid @enderror"
                               value="{{ old('priority', $role->priority) }}">
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-6 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active"
                                   name="is_active" value="1"
                                   @checked(old('is_active', $role->is_active))>
                            <label class="form-check-label" for="is_active">Role actif</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <small class="text-muted">
                            Cree le {{ $role->created_at?->format('d/m/Y H:i') ?? '—' }}
                            &bull; {{ $role->users_count ?? $role->users()->count() }} utilisateur(s)
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Permissions</h2>
                    @if(!($role->is_system && in_array('*', $role->permissions ?? [])))
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPermissions(true)">
                                <i class="bi bi-check-all me-1"></i>Tout cocher
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPermissions(false)">
                                <i class="bi bi-x-lg me-1"></i>Tout decocher
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body" style="max-height:480px;overflow-y:auto;">
                    @if($role->is_system && in_array('*', $role->permissions ?? []))
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-shield-lock me-2"></i>
                            Ce role a les permissions <strong>wildcard (*)</strong> — toutes les permissions sont accordees et ne peuvent pas etre modifiees ici.
                        </div>
                    @else
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
                                                   @checked(collect(old('permissions', $role->permissions ?? []))->contains($permission))>
                                            <label class="form-check-label small" for="perm_{{ str_replace('.', '_', $permission) }}">
                                                {{ $action }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Enregistrer
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
