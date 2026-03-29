@extends('admin.layouts.catmin')

@section('page_title', 'Monitoring Center')

@section('content')
<x-admin.crud.page-header
    title="Monitoring Center"
    subtitle="Observabilite transverse: sante systeme, incidents correles, historique et actions ops."
>
    <form method="POST" action="{{ admin_route('monitoring.snapshot') }}" class="d-inline">
        @csrf
        <button class="btn btn-sm btn-outline-primary" type="submit">
            <i class="bi bi-arrow-repeat me-1"></i>Snapshot maintenant
        </button>
    </form>
    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('monitoring.incidents') }}">Incidents detail</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    @php($global = $report['global'] ?? [])
    @php($status = (string) ($global['status'] ?? 'ok'))
    @php($score = (int) ($global['score'] ?? 100))
    @php($statusBadge = $status === 'critical' ? 'text-bg-danger' : ($status === 'degraded' ? 'text-bg-warning' : ($status === 'warning' ? 'text-bg-info' : 'text-bg-success')))

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="small text-muted mb-1">Etat global</p>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge {{ $statusBadge }}">{{ strtoupper($status) }}</span>
                        <span class="small text-muted">Dernier check: {{ \Illuminate\Support\Carbon::parse($global['checked_at'] ?? now())->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <p class="display-6 mb-0">{{ $score }}/100</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Historique snapshots (24 derniers)</h2></div>
                <div class="card-body">
                    @if(!empty($report['history']))
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Date</th><th>Status</th><th>Score</th><th>Open</th><th>Critical</th></tr></thead>
                                <tbody>
                                @foreach($report['history'] as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row['created_at'])->format('d/m H:i') }}</td>
                                        <td>{{ strtoupper($row['status']) }}</td>
                                        <td>{{ $row['score'] }}</td>
                                        <td>{{ $row['incidents_open'] }}</td>
                                        <td>{{ $row['incidents_critical'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Aucun snapshot disponible.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-admin.crud.table-card title="Health checks consolides" :count="count($report['checks'] ?? [])" :empty-colspan="8" empty-message="Aucun check disponible.">
        <x-slot:head>
            <tr>
                <th>Domaine</th>
                <th>Etat</th>
                <th>Titre</th>
                <th>Message</th>
                <th>Metric</th>
                <th>Seuil</th>
                <th>Actions</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach(($report['checks'] ?? []) as $check)
                @php($rowStatus = (string) ($check['status'] ?? 'ok'))
                @php($rowBadge = $rowStatus === 'critical' ? 'text-bg-danger' : ($rowStatus === 'degraded' ? 'text-bg-warning' : ($rowStatus === 'warning' ? 'text-bg-info' : 'text-bg-success')))
                <tr>
                    <td><span class="badge text-bg-light">{{ $check['domain'] }}</span></td>
                    <td><span class="badge {{ $rowBadge }}">{{ strtoupper($rowStatus) }}</span></td>
                    <td>{{ $check['title'] }}</td>
                    <td>{{ $check['message'] }}</td>
                    <td>{{ $check['metric'] }}</td>
                    <td>{{ $check['threshold'] }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach(($check['actions'] ?? []) as $action)
                                @if(!empty($action['url']))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
                                @endif
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <x-admin.crud.table-card title="Incidents ouverts" :count="count($report['incidents'] ?? [])" :empty-colspan="6" empty-message="Aucun incident ouvert.">
                <x-slot:head>
                    <tr><th>Domaine</th><th>Statut</th><th>Titre</th><th>Occurrences</th><th>Derniere activite</th><th>Message</th></tr>
                </x-slot:head>
                <x-slot:rows>
                    @foreach(($report['incidents'] ?? []) as $incident)
                        <tr>
                            <td>{{ $incident->domain }}</td>
                            <td>{{ strtoupper($incident->status) }}</td>
                            <td>{{ $incident->title }}</td>
                            <td>{{ $incident->occurrences }}</td>
                            <td>{{ optional($incident->last_seen_at)->format('d/m/Y H:i:s') ?: 'n/a' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) $incident->message, 120) }}</td>
                        </tr>
                    @endforeach
                </x-slot:rows>
            </x-admin.crud.table-card>
        </div>

        <div class="col-12 col-xl-5">
            <x-admin.crud.table-card title="Alertes correlees (24h)" :count="count($report['alert_clusters'] ?? [])" :empty-colspan="5" empty-message="Aucune alerte a correler.">
                <x-slot:head>
                    <tr><th>Type</th><th>Severite</th><th>Titre</th><th>Occurrences</th><th>Message</th></tr>
                </x-slot:head>
                <x-slot:rows>
                    @foreach(($report['alert_clusters'] ?? []) as $row)
                        <tr>
                            <td>{{ $row['domain'] }}</td>
                            <td>{{ strtoupper($row['severity']) }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['occurrences'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) $row['message'], 90) }}</td>
                        </tr>
                    @endforeach
                </x-slot:rows>
            </x-admin.crud.table-card>
        </div>
    </div>
</div>
@endsection
