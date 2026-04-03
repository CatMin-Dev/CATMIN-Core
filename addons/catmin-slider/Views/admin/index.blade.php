@extends('admin.layouts.catmin')

@section('page_title', 'Sliders · Liste')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Sliders</h1>
        <p class="text-muted mb-0">Gérez vos sliders, carrousels et vitrines de contenu.</p>
    </div>
    <div>
        <a href="{{ route('admin.slider.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nouveau slider
        </a>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.slider.index') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Recherche</label>
                    <input type="text" name="q" class="form-control" placeholder="Nom, slug…" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="active" class="form-select">
                        <option value="">Tous</option>
                        <option value="1" @selected(request('active') === '1')>Actif</option>
                        <option value="0" @selected(request('active') === '0')>Inactif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Sliders</strong>
            <span class="badge text-bg-light">{{ $sliders->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Période</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sliders as $slider)
                        <tr>
                            <td>
                                <strong>{{ $slider->name }}</strong>
                                @if($slider->description)
                                    <br><small class="text-muted">{{ Str::limit($slider->description, 60) }}</small>
                                @endif
                            </td>
                            <td><code class="text-muted small">{{ $slider->slug }}</code></td>
                            <td>
                                @php($typeLabels = ['fullwidth' => 'Pleine largeur', 'carousel' => 'Carrousel', 'grid' => 'Grille'])
                                @php($typeIcons = ['fullwidth' => 'bi-display', 'carousel' => 'bi-infinity', 'grid' => 'bi-grid-3x3-gap'])
                                <span class="badge text-bg-secondary">
                                    <i class="bi {{ $typeIcons[$slider->type] ?? 'bi-square' }} me-1"></i>
                                    {{ $typeLabels[$slider->type] ?? $slider->type }}
                                </span>
                            </td>
                            <td>
                                @if($slider->is_active)
                                    <span class="badge text-bg-success">Actif</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactif</span>
                                @endif
                            </td>
                            <td>
                                @if($slider->starts_at || $slider->ends_at)
                                    <small class="text-muted">
                                        {{ $slider->starts_at?->format('d/m/Y') ?? '∞' }}
                                        →
                                        {{ $slider->ends_at?->format('d/m/Y') ?? '∞' }}
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.slider.edit', $slider->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>Éditer
                                </a>
                                <form method="POST" action="{{ route('admin.slider.toggle', $slider->id) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary">
                                        <i class="bi {{ $slider->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.slider.destroy', $slider->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce slider ?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun slider.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sliders->hasPages())
            <div class="card-footer">{{ $sliders->links() }}</div>
        @endif
    </div>
</div>
@endsection
