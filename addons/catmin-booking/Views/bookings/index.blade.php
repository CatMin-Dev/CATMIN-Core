@extends('admin.layouts.catmin')

@section('page_title', 'Booking · Réservations')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Réservations</h1>
        <p class="text-muted mb-0">Suivi des réservations clients et statuts.</p>
    </div>
</header>

<div class="catmin-page-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card mb-4">
        <div class="card-header bg-white"><strong>Nouvelle réservation (admin)</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.booking.bookings.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Créneau</label>
                    <select name="booking_slot_id" class="form-select" required>
                        <option value="">Choisir…</option>
                        @foreach($slots as $slot)
                            <option value="{{ $slot->id }}">#{{ $slot->id }} · {{ optional($slot->start_at)->format('d/m H:i') }} - {{ optional($slot->end_at)->format('H:i') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nom</label>
                    <input type="text" name="customer_name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="customer_email" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Téléphone</label>
                    <input type="text" name="customer_phone" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Créer réservation</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Liste</strong>
            <span class="badge text-bg-light">{{ $bookings->total() }}</span>
        </div>
        <div class="table-responsive catmin-table-scroll">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Créneau</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td><code>{{ $booking->confirmation_code }}</code></td>
                            <td>
                                <strong>{{ $booking->customer_name }}</strong><br>
                                <small class="text-muted">{{ $booking->customer_email }}</small>
                            </td>
                            <td>{{ $booking->service->name ?? '—' }}</td>
                            <td>{{ optional($booking->slot->start_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $booking->status === 'confirmed' ? 'text-bg-success' : ($booking->status === 'cancelled' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.booking.bookings.status', $booking->id) }}" class="d-inline-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="form-select form-select-sm">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" @selected($status === $booking->status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Mettre à jour</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune réservation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bookings->hasPages())
            <div class="card-footer">{{ $bookings->links() }}</div>
        @endif
    </div>
</div>
@endsection
