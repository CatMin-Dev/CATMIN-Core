@extends('admin.layouts.catmin')

@section('page_title', 'Booking · Calendrier')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1">Calendrier des créneaux</h1>
        <p class="text-muted mb-0">Vue interactive jour/semaine des disponibilités.</p>
    </div>
    <div class="btn-group" role="group" aria-label="mode">
        <button type="button" class="btn btn-outline-secondary active" id="mode-day">Jour</button>
        <button type="button" class="btn btn-outline-secondary" id="mode-week">Semaine</button>
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
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="text-center text-muted py-4">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(() => {
    const dateInput = document.getElementById('calendar-date');
    const btnRefresh = document.getElementById('calendar-refresh');
    const btnDay = document.getElementById('mode-day');
    const btnWeek = document.getElementById('mode-week');
    const tbody = document.querySelector('#calendar-table tbody');

    let mode = 'day';

    const setMode = (nextMode) => {
        mode = nextMode;
        btnDay.classList.toggle('active', mode === 'day');
        btnWeek.classList.toggle('active', mode === 'week');
    };

    const toIsoDate = (d) => d.toISOString().slice(0, 10);

    const rangeFromDate = (baseDate) => {
        const from = new Date(baseDate + 'T00:00:00');
        const to = new Date(from);
        if (mode === 'day') {
            to.setDate(to.getDate() + 1);
        } else {
            to.setDate(to.getDate() + 7);
        }
        return { from: toIsoDate(from), to: toIsoDate(to) };
    };

    const load = async () => {
        const refDate = dateInput.value || new Date().toISOString().slice(0, 10);
        const range = rangeFromDate(refDate);

        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Chargement…</td></tr>';

        const url = `{{ route('admin.booking.api.calendar') }}?from=${encodeURIComponent(range.from)}&to=${encodeURIComponent(range.to)}`;
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const payload = await response.json();

        const slots = (payload.data && payload.data.slots) ? payload.data.slots : [];

        if (!Array.isArray(slots) || slots.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Aucun créneau sur la période.</td></tr>';
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
            </tr>`;
        }).join('');
    };

    btnDay.addEventListener('click', () => { setMode('day'); load(); });
    btnWeek.addEventListener('click', () => { setMode('week'); load(); });
    btnRefresh.addEventListener('click', load);

    load();
})();
</script>
@endsection
