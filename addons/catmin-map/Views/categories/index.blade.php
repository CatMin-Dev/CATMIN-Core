@extends('admin.layouts.catmin')

@section('page_title', 'Map · Catégories')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-tags me-2"></i>Catégories de lieux</h1>
        <p class="text-muted mb-0">Organiser vos lieux par catégorie.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.map.locations.index') }}" class="btn btn-sm btn-outline-secondary">Lieux</a>
        <a href="{{ route('admin.map.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-map me-1"></i>Carte</a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ implode(', ', $errors->all()) }}</div>@endif

    <div class="row g-4">
        {{-- Création --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header bg-white"><strong>Nouvelle catégorie</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.map.categories.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="120">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Couleur</label>
                            <input type="color" name="color" value="#3B82F6" class="form-control form-control-color w-100">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Icône Bootstrap</label>
                            <input type="text" name="icon" class="form-control" placeholder="geo-alt" maxlength="64">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Liste --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Catégories</strong>
                    <span class="badge text-bg-light">{{ $categories->count() }}</span>
                </div>
                <div class="table-responsive catmin-table-scroll">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Couleur / Icône</th>
                                <th>Lieux</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td>
                                        <strong>{{ $cat->name }}</strong>
                                        @if($cat->description)
                                            <br><small class="text-muted">{{ Str::limit($cat->description, 60) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="d-inline-block rounded" style="width:20px;height:20px;background:{{ $cat->color }};vertical-align:middle;"></span>
                                        @if($cat->icon)
                                            <i class="bi bi-{{ $cat->icon }} ms-1"></i>
                                        @endif
                                    </td>
                                    <td><span class="badge text-bg-secondary">{{ $cat->locations_count }}</span></td>
                                    <td>
                                        @if($cat->active)
                                            <span class="badge text-bg-success">Actif</span>
                                        @else
                                            <span class="badge text-bg-warning">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCat{{ $cat->id }}">
                                            Modifier
                                        </button>
                                        <form method="POST" action="{{ route('admin.map.categories.destroy', $cat->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Supprimer cette catégorie ?')">
                                                Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit modal --}}
                                <div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('admin.map.categories.update', $cat->id) }}" class="modal-content">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier « {{ $cat->name }} »</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body row g-3">
                                                <div class="col-12">
                                                    <label class="form-label">Nom</label>
                                                    <input type="text" name="name" value="{{ $cat->name }}" class="form-control" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Couleur</label>
                                                    <input type="color" name="color" value="{{ $cat->color }}" class="form-control form-control-color w-100">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Icône Bootstrap</label>
                                                    <input type="text" name="icon" value="{{ $cat->icon }}" class="form-control" placeholder="geo-alt">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Description</label>
                                                    <textarea name="description" class="form-control" rows="2">{{ $cat->description }}</textarea>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="active" value="1" id="active{{ $cat->id }}"
                                                            {{ $cat->active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="active{{ $cat->id }}">Actif</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Aucune catégorie.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
