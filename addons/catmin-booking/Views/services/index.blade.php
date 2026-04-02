@extends('admin.layouts.catmin')

@section('page_title', 'Booking · Services')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Services réservables</h1>
        <p class="text-muted mb-0">Configurez les services vendables avec durée et prix.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header bg-white"><strong>Nouveau service</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.booking.services.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" placeholder="auto si vide">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Durée (min)</label>
                            <input type="number" min="5" max="480" step="5" name="duration_minutes" class="form-control" value="30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Prix (€)</label>
                            <input type="number" min="0" step="0.01" name="price" class="form-control" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="col-12 form-check ms-2">
                            <input class="form-check-input" type="checkbox" value="1" name="is_active" id="service_active" checked>
                            <label class="form-check-label" for="service_active">Actif</label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Liste des services</strong>
                    <span class="badge text-bg-light">{{ $services->total() }}</span>
                </div>
                <div class="table-responsive catmin-table-scroll">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Durée</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->name }}</strong><br>
                                        <small class="text-muted">{{ $item->slug }}</small>
                                    </td>
                                    <td>{{ $item->duration_minutes }} min</td>
                                    <td>{{ number_format($item->price_cents / 100, 2) }} €</td>
                                    <td>
                                        <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $item->is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.booking.services.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                        <form method="POST" action="{{ route('admin.booking.services.destroy', $item->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce service ?')">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Aucun service.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($services->hasPages())
                    <div class="card-footer">{{ $services->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
