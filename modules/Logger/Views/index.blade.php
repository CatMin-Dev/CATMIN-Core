@extends('admin.layouts.catmin')

@section('page_title', 'Logs')

@section('content')
<x-admin.crud.page-header
    title="System Logs"
    subtitle="Historique des actions admin et erreurs applicatives."
/>

<div class="catmin-page-body">
    <x-admin.crud.table-card title="Filtres" :count="$logs->count()" :empty-colspan="1" empty-message="Aucun log.">
        <x-slot:head>
            <tr>
                <th>Recherche</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            <tr>
                <td>
                    <form method="GET" action="{{ route('admin.logger.index') }}" class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label for="filter-q" class="form-label">Recherche</label>
                            <input id="filter-q" name="q" type="text" class="form-control" value="{{ $searchQuery }}" placeholder="message, event, url">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filter-level" class="form-label">Niveau</label>
                            <select id="filter-level" name="level" class="form-select">
                                <option value="">Tous</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level }}" @selected($selectedLevel === $level)>{{ strtoupper($level) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filter-channel" class="form-label">Canal</label>
                            <select id="filter-channel" name="channel" class="form-select">
                                <option value="">Tous</option>
                                @foreach($channels as $channel)
                                    <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ $channel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filter-event" class="form-label">Evenement</label>
                            <select id="filter-event" name="event" class="form-select">
                                <option value="">Tous</option>
                                @foreach($events as $event)
                                    <option value="{{ $event }}" @selected($selectedEvent === $event)>{{ $event }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="filter-admin" class="form-label">Admin</label>
                            <select id="filter-admin" name="admin" class="form-select">
                                <option value="">Tous</option>
                                @foreach($admins as $admin)
                                    <option value="{{ $admin }}" @selected($selectedAdmin === $admin)>{{ $admin }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="filter-from" class="form-label">Du</label>
                            <input id="filter-from" name="from" type="date" class="form-control" value="{{ $selectedFrom }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="filter-to" class="form-label">Au</label>
                            <input id="filter-to" name="to" type="date" class="form-control" value="{{ $selectedTo }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="filter-status" class="form-label">HTTP</label>
                            <input id="filter-status" name="status" type="number" min="100" max="599" class="form-control" value="{{ $selectedStatus }}" placeholder="500">
                        </div>
                        <div class="col-6 col-md-3 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Filtrer</button>
                            <a class="btn btn-outline-secondary" href="{{ route('admin.logger.index') }}">Reset</a>
                        </div>
                    </form>
                </td>
            </tr>
        </x-slot:rows>
    </x-admin.crud.table-card>

    <x-admin.crud.table-card title="Entrees" :count="$logs->count()" :empty-colspan="8" empty-message="Aucun log enregistre.">
        <x-slot:head>
            <tr>
                <th>Date</th>
                <th>Canal</th>
                <th>Niveau</th>
                <th>Evenement</th>
                <th>Message</th>
                <th>Admin</th>
                <th>HTTP</th>
                <th>Contexte</th>
            </tr>
        </x-slot:head>

        <x-slot:rows>
            @foreach($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') ?: 'n/a' }}</td>
                    <td><span class="badge text-bg-light">{{ $log->channel }}</span></td>
                    <td>
                        @php($levelClass = $log->level === 'error' ? 'text-bg-danger' : ($log->level === 'warning' ? 'text-bg-warning' : 'text-bg-info'))
                        <span class="badge {{ $levelClass }}">{{ strtoupper($log->level) }}</span>
                    </td>
                    <td>{{ $log->event }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($log->message, 120) }}</td>
                    <td>{{ $log->admin_username ?: ($log->channel === 'application' ? 'system' : 'n/a') }}</td>
                    <td>
                        @if($log->method)
                            <span class="badge text-bg-secondary">{{ $log->method }}</span>
                        @endif
                        @if($log->status_code)
                            <span class="badge text-bg-light">{{ $log->status_code }}</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($log->context))
                            <details>
                                <summary>Voir</summary>
                                <pre class="small mb-0">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @else
                            <span class="text-muted">n/a</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>
@endsection
