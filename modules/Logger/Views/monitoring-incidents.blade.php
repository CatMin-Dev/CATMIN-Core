@extends('admin.layouts.catmin')

@section('page_title', 'Monitoring Incidents')

@section('content')
<x-admin.crud.page-header
    title="Monitoring Incidents"
    subtitle="Vue consolidee des incidents warning/degraded/critical/recovered."
>
    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('monitoring.index') }}">Monitoring center</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ admin_route('monitoring.incidents') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="status">Statut</label>
                    <select id="status" name="status" class="form-select">
                        <option value="open" @selected($selectedStatus === 'open')>Ouverts</option>
                        <option value="recovered" @selected($selectedStatus === 'recovered')>Recovered</option>
                        <option value="all" @selected($selectedStatus === 'all')>Tous</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="domain">Domaine</label>
                    <select id="domain" name="domain" class="form-select">
                        <option value="">Tous</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain }}" @selected($selectedDomain === $domain)>{{ $domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Filtrer</button>
                    <a class="btn btn-outline-secondary" href="{{ admin_route('monitoring.incidents') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <x-admin.crud.table-card title="Incidents" :count="$incidents->total()" :empty-colspan="8" empty-message="Aucun incident.">
        <x-slot:head>
            <tr>
                <th>Domaine</th>
                <th>Severite</th>
                <th>Status</th>
                <th>Titre</th>
                <th>Occurrences</th>
                <th>First seen</th>
                <th>Last seen</th>
                <th>Recovered</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($incidents as $incident)
                <tr>
                    <td>{{ $incident->domain }}</td>
                    <td>{{ strtoupper($incident->severity) }}</td>
                    <td>{{ strtoupper($incident->status) }}</td>
                    <td>
                        <div class="fw-semibold">{{ $incident->title }}</div>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit((string) $incident->message, 120) }}</div>
                    </td>
                    <td>{{ $incident->occurrences }}</td>
                    <td>{{ optional($incident->first_seen_at)->format('d/m/Y H:i:s') ?: 'n/a' }}</td>
                    <td>{{ optional($incident->last_seen_at)->format('d/m/Y H:i:s') ?: 'n/a' }}</td>
                    <td>{{ optional($incident->recovered_at)->format('d/m/Y H:i:s') ?: '-' }}</td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    @if($incidents->hasPages())
        <div class="mt-3">
            <x-admin.crud.pagination :paginator="$incidents" />
        </div>
    @endif
</div>
@endsection
