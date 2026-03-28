@extends('admin.layouts.catmin')

@section('page_title', 'Alertes Opérationnelles')

@section('content')
<x-admin.crud.page-header
    title="Alertes Opérationnelles"
    subtitle="Incidents critiques, erreurs webhook et anomalies de production."
/>

<div class="catmin-page-body">
    <x-admin.crud.table-card title="Filtres" :count="$alerts->count()" :empty-colspan="1" empty-message="Aucune alerte.">
        <x-slot:head>
            <tr>
                <th>Recherche</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            <tr>
                <td>
                    <form method="GET" action="{{ route('admin.logger.alerts.index') }}" class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label for="severity" class="form-label">Sévérité</label>
                            <select id="severity" name="severity" class="form-select">
                                <option value="">Toutes</option>
                                <option value="critical" @selected($selectedSeverity === 'critical')>CRITICAL</option>
                                <option value="warning" @selected($selectedSeverity === 'warning')>WARNING</option>
                                <option value="info" @selected($selectedSeverity === 'info')>INFO</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="type" class="form-label">Type</label>
                            <select id="type" name="type" class="form-select">
                                <option value="">Tous</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" @selected($selectedType === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="state" class="form-label">État</label>
                            <select id="state" name="state" class="form-select">
                                <option value="open" @selected($selectedState === 'open')>Ouvertes</option>
                                <option value="ack" @selected($selectedState === 'ack')>Acquittées</option>
                                <option value="all" @selected($selectedState === 'all')>Toutes</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Filtrer</button>
                            <a class="btn btn-outline-secondary" href="{{ route('admin.logger.alerts.index') }}">Reset</a>
                        </div>
                    </form>
                </td>
            </tr>
        </x-slot:rows>
    </x-admin.crud.table-card>

    <form method="POST" action="{{ route('admin.logger.alerts.acknowledge') }}">
        @csrf
        <x-admin.crud.table-card title="Alertes" :count="$alerts->total()" :empty-colspan="8" empty-message="Aucune alerte trouvée.">
            <x-slot:head>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="select-all"></th>
                    <th>Date</th>
                    <th>Sévérité</th>
                    <th>Type</th>
                    <th>Titre</th>
                    <th>Message</th>
                    <th>État</th>
                    <th>Contexte</th>
                </tr>
            </x-slot:head>

            <x-slot:rows>
                @foreach($alerts as $alert)
                    <tr>
                        <td>
                            @if(!$alert->acknowledged)
                                <input type="checkbox" name="ids[]" value="{{ $alert->id }}" class="alert-checkbox">
                            @endif
                        </td>
                        <td>{{ optional($alert->created_at)->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <span class="badge {{ $alert->severity === 'critical' ? 'text-bg-danger' : ($alert->severity === 'warning' ? 'text-bg-warning' : 'text-bg-info') }}">
                                {{ strtoupper($alert->severity) }}
                            </span>
                        </td>
                        <td>{{ $alert->alert_type }}</td>
                        <td>{{ $alert->title }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($alert->message, 140) }}</td>
                        <td>
                            @if($alert->acknowledged)
                                <span class="badge text-bg-success">Acquittée</span>
                                <div class="small text-muted">{{ $alert->acknowledged_by ?: 'n/a' }}</div>
                            @else
                                <span class="badge text-bg-danger">Ouverte</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($alert->context))
                                <details>
                                    <summary>Voir</summary>
                                    <pre class="small mb-0">{{ json_encode($alert->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @else
                                <span class="text-muted">n/a</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-slot:rows>
        </x-admin.crud.table-card>

        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <button type="submit" class="btn btn-warning">Acquitter la sélection</button>
        </div>

        @if($alerts->hasPages())
            <div class="mt-2">
                <x-admin.crud.pagination :paginator="$alerts" />
            </div>
        @endif
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all');
        if (!selectAll) return;
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.alert-checkbox').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    });
</script>
@endsection
