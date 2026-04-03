@extends('admin.layouts.catmin')

@section('page_title', 'Map · Lieux')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-geo-alt me-2"></i>Lieux</h1>
        <p class="text-muted mb-0">Gérez les localisations géographiques.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.map.categories.index') }}" class="btn btn-sm btn-outline-secondary">Catégories</a>
        <a href="{{ route('admin.map.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-map me-1"></i>Carte</a>
        <a href="{{ route('admin.map.locations.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nouveau lieu</a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.map.locations.index') }}" class="card mb-4">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label mb-1 small">Recherche</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nom, adresse…">
            </div>
            <div>
                <label class="form-label mb-1 small">Catégorie</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1 small">Ville</label>
                <input type="text" name="city" value="{{ request('city') }}" class="form-control form-control-sm" placeholder="Paris…">
            </div>
            <div>
                <label class="form-label mb-1 small">Statut</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publié</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivé</option>
                </select>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-primary">Filtrer</button>
                <a href="{{ route('admin.map.locations.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Lieux</strong>
            <span class="badge text-bg-light">{{ $locations->total() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Adresse</th>
                        <th>Coords</th>
                        <th>Statut</th>
                        <th>Intégrations</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $loc)
                        <tr>
                            <td>
                                @if($loc->featured)
                                    <i class="bi bi-star-fill text-warning me-1" title="Mis en avant"></i>
                                @endif
                                <strong>{{ $loc->name }}</strong>
                            </td>
                            <td>
                                @if($loc->category)
                                    <span class="d-inline-block rounded me-1" style="width:10px;height:10px;background:{{ $loc->category->color }};vertical-align:middle;"></span>
                                    {{ $loc->category->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <small>
                                    {{ $loc->address ?? '' }}
                                    @if($loc->city) <br><span class="text-muted">{{ $loc->city }}</span>@endif
                                </small>
                            </td>
                            <td>
                                @if($loc->hasCoordinates())
                                    <span class="badge text-bg-success">
                                        <i class="bi bi-crosshair me-1"></i>OK
                                    </span>
                                @else
                                    <span class="badge text-bg-warning">Manquant</span>
                                @endif
                            </td>
                            <td>
                                @if($loc->status === 'published')
                                    <span class="badge text-bg-success">Publié</span>
                                @elseif($loc->status === 'draft')
                                    <span class="badge text-bg-warning">Brouillon</span>
                                @else
                                    <span class="badge text-bg-secondary">Archivé</span>
                                @endif
                            </td>
                            <td>
                                @if($loc->linked_event_id)
                                    <span class="badge text-bg-info" title="Lié à l'événement #{{ $loc->linked_event_id }}">Evt</span>
                                @endif
                                @if($loc->linked_shop_id)
                                    <span class="badge text-bg-primary" title="Lié au shop #{{ $loc->linked_shop_id }}">Shop</span>
                                @endif
                                @if($loc->linked_page_id)
                                    <span class="badge text-bg-secondary" title="Lié à la page #{{ $loc->linked_page_id }}">Page</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.map.locations.edit', $loc->id) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                <form method="POST" action="{{ route('admin.map.locations.destroy', $loc->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Supprimer ce lieu ?')">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucun lieu trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($locations->hasPages())
            <div class="card-footer bg-white">
                {{ $locations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
