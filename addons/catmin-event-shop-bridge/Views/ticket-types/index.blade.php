@extends('admin.layouts.catmin')

@section('page_title', 'Event-Shop Bridge')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Event-Shop Bridge</h1>
        <p class="text-muted mb-0">Types de billets exposes comme produits shop et suivi d'emission.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Creer un type de billet</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.event-shop-bridge.ticket-types.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Evenement</label>
                    <select name="event_id" class="form-select" required>
                        <option value="">Choisir</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }} ({{ $event->start_at->format('d/m/Y') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Nom billet</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" min="0" name="price" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Allocation</label><input type="number" min="1" name="allocation" class="form-control" placeholder="illimite"></div>
                <div class="col-md-2"><label class="form-label">Statut</label><select name="status" class="form-select"><option value="active">active</option><option value="inactive">inactive</option></select></div>
                <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Creer</button></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="auto_cancel_on_order_cancel" id="auto_cancel_on_order_cancel" value="1" checked><label class="form-check-label" for="auto_cancel_on_order_cancel">Annuler automatiquement les billets si la commande shop est annulee</label></div></div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0">Types de billets</h2><span class="badge text-bg-light">{{ $ticketTypes->total() }}</span></div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Evenement</th><th>Billet</th><th>Produit shop</th><th>Prix</th><th>Stock restant</th><th>Statut</th></tr></thead>
                <tbody>
                @forelse($ticketTypes as $ticketType)
                    <tr>
                        <td>{{ $ticketType->event?->title ?? '—' }}</td>
                        <td><strong>{{ $ticketType->name }}</strong><br><small class="text-muted">{{ $ticketType->sku }}</small></td>
                        <td>{{ $ticketType->product?->name ?? '—' }}</td>
                        <td>{{ number_format((float) $ticketType->price, 2, '.', ' ') }} EUR</td>
                        <td>{{ $ticketType->product?->stock_quantity ?? '—' }}</td>
                        <td><span class="badge {{ $ticketType->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $ticketType->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun type de billet bridge.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($ticketTypes->hasPages())
            <div class="card-footer">{{ $ticketTypes->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0">Derniers liens commande -> billets</h2><span class="badge text-bg-light">{{ $recentLinks->count() }}</span></div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Commande</th><th>Type billet</th><th>Source</th><th>Statut</th><th>Ticket</th><th>Erreur</th></tr></thead>
                <tbody>
                @forelse($recentLinks as $link)
                    <tr>
                        <td>{{ $link->order?->order_number ?? '—' }}</td>
                        <td>{{ $link->ticketType?->name ?? '—' }}</td>
                        <td><code>{{ $link->source_key }}</code></td>
                        <td><span class="badge {{ $link->status === 'issued' ? 'text-bg-success' : ($link->status === 'cancelled' ? 'text-bg-danger' : ($link->status === 'failed_capacity' ? 'text-bg-warning' : 'text-bg-secondary')) }}">{{ $link->status }}</span></td>
                        <td>{{ $link->ticket?->ticket_number ?? '—' }}</td>
                        <td>{{ $link->integration_error ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun lien bridge recent.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection