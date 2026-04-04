@extends('admin.layouts.catmin')

@section('page_title', 'Booking · Calendrier')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Calendrier des créneaux</h1>
        <p class="text-muted mb-0">Vue interactive jour/semaine/mois avec détails de disponibilité.</p>
    </div>
    <div class="btn-group" role="group" aria-label="mode">
        <button type="button" class="btn btn-outline-secondary active" id="mode-day">Jour</button>
        <button type="button" class="btn btn-outline-secondary" id="mode-week">Semaine</button>
        <button type="button" class="btn btn-outline-secondary" id="mode-month">Mois</button>
    </div>
</header>

<div class="catmin-page-body">
    <div class="card mb-3">
        <div class="card-body d-flex gap-3 align-items-end flex-wrap">
            <div>
                <label class="form-label">Date de référence</label>
                <input type="date" id="calendar-date" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div>
                <label class="form-label">Service</label>
                <select id="calendar-service" class="form-select">
                    <option value="">Tous les services</option>
                    @foreach($servicesList as $serviceItem)
                        <option value="{{ $serviceItem->id }}">{{ $serviceItem->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="btn btn-primary" id="calendar-refresh">Charger</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <strong>Créneaux</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-striped mb-0" id="calendar-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Capacité</th>
                        <th>Réservé</th>
                        <th>Disponible</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="8" class="text-center text-muted py-4">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-3 d-none" id="slot-details-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Détails créneau</strong>
            <button class="btn btn-sm btn-outline-secondary" id="slot-details-close" type="button">Fermer</button>
        </div>
        <div class="card-body" id="slot-details-content"></div>
    </div>
</div>

<script>
(() => {
    const dateInput = document.getElementById('calendar-date');
    const btnRefresh = document.getElementById('calendar-refresh');
    const btnDay = document.getElementById('mode-day');
    const btnWeek = document.getElementById('mode-week');
    const btnMonth = document.getElementById('mode-month');
    const serviceSelect = document.getElementById('calendar-service');
    const tbody = document.querySelector('#calendar-table tbody');
    const slotDetailsCard = document.getElementById('slot-details-card');
    const slotDetailsContent = document.getElementById('slot-details-content');
    const slotDetailsClose = document.getElementById('slot-details-close');

    let mode = 'day';

    const setMode = (nextMode) => {
        mode = nextMode;
        btnDay.classList.toggle('active', mode === 'day');
        btnWeek.classList.toggle('active', mode === 'week');
        btnMonth.classList.toggle('active', mode === 'month');
    };

    const toIsoDate = (d) => d.toISOString().slice(0, 10);

    const rangeFromDate = (baseDate) => {
        const from = new Date(baseDate + 'T00:00:00');
        const to = new Date(from);
        if (mode === 'day') {
            to.setDate(to.getDate() + 1);
        } else if (mode === 'week') {
            to.setDate(to.getDate() + 7);
        } else {
            to.setDate(to.getDate() + 31);
        }
        return { from: toIsoDate(from), to: toIsoDate(to) };
    };

    const loadSlotDetails = async (slotId) => {
        const url = `{{ route('admin.booking.api.slot-details', ['bookingSlot' => 0]) }}`.replace('/0', `/${slotId}`);
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const payload = await response.json();
        const slot = payload?.data?.slot;
        const bookings = payload?.data?.bookings || [];

        if (!slot) {
            return;
        }

        const bookingsHtml = bookings.length
            ? bookings.map((booking) => `<tr><td>${booking.confirmation_code}</td><td>${booking.customer_name}</td><td>${booking.customer_email}</td><td>${booking.status}</td></tr>`).join('')
            : '<tr><td colspan="4" class="text-muted text-center">Aucune reservation</td></tr>';

        slotDetailsContent.innerHTML = `
            <p class="mb-1"><strong>Service:</strong> ${slot.service_name || '—'}</p>
            <p class="mb-1"><strong>Début:</strong> ${slot.start_at ? new Date(slot.start_at).toLocaleString() : '—'}</p>
            <p class="mb-1"><strong>Fin:</strong> ${slot.end_at ? new Date(slot.end_at).toLocaleString() : '—'}</p>
            <p class="mb-1"><strong>Statut:</strong> ${slot.status || 'open'}</p>
            <p class="mb-3"><strong>Capacité restante:</strong> ${slot.remaining}</p>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Code</th><th>Client</th><th>Email</th><th>Statut</th></tr></thead>
                    <tbody>${bookingsHtml}</tbody>
                </table>
            </div>
        `;

        slotDetailsCard.classList.remove('d-none');
    };

    const load = async () => {
        const refDate = dateInput.value || new Date().toISOString().slice(0, 10);
        const range = rangeFromDate(refDate);

        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Chargement…</td></tr>';

        const serviceId = serviceSelect.value;

        const url = `{{ route('admin.booking.api.calendar') }}?from=${encodeURIComponent(range.from)}&to=${encodeURIComponent(range.to)}&booking_service_id=${encodeURIComponent(serviceId)}`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const payload = await response.json();

        const slots = (payload.data && payload.data.slots) ? payload.data.slots : [];

        if (!Array.isArray(slots) || slots.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Aucun créneau sur la période.</td></tr>';
            return;
        }

        tbody.innerHTML = slots.map((slot) => {
            const start = slot.start_at ? new Date(slot.start_at) : null;
            const end = slot.end_at ? new Date(slot.end_at) : null;
            return `<tr>
                <td>${slot.service_name || '—'}</td>
                <td>${start ? start.toLocaleString() : '—'}</td>
                <td>${end ? end.toLocaleString() : '—'}</td>
                <td>${slot.capacity}</td>
                <td>${slot.booked_count}</td>
                <td><span class="badge ${slot.remaining > 0 ? 'text-bg-success' : 'text-bg-danger'}">${slot.remaining}</span></td>
                <td><span class="badge ${slot.status === 'open' ? 'text-bg-success' : (slot.status === 'closed' ? 'text-bg-warning' : 'text-bg-danger')}">${slot.status || 'open'}</span></td>
                <td><button class="btn btn-sm btn-outline-primary slot-details" data-slot-id="${slot.id}" type="button">Details</button></td>
            </tr>`;
        }).join('');

        Array.from(document.querySelectorAll('.slot-details')).forEach((button) => {
            button.addEventListener('click', () => loadSlotDetails(button.getAttribute('data-slot-id')));
        });
    };

    btnDay.addEventListener('click', () => { setMode('day'); load(); });
    btnWeek.addEventListener('click', () => { setMode('week'); load(); });
    btnMonth.addEventListener('click', () => { setMode('month'); load(); });
    btnRefresh.addEventListener('click', load);
    slotDetailsClose.addEventListener('click', () => slotDetailsCard.classList.add('d-none'));

    load();
})();
</script>
@endsection
