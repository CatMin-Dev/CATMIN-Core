@extends('admin.layouts.catmin')

@section('page_title', 'Performance Center')

@section('content')
<x-admin.crud.page-header
    title="Performance Center"
    subtitle="Profiling pragmatique des routes critiques, des requetes lentes et des jobs longs."
>
    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('monitoring.index') }}">Monitoring center</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('logger.index', ['channel' => 'performance']) }}">Logs performance</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    @php($summary = $report['summary'] ?? [])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ admin_route('performance.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="hours">Fenetre (heures)</label>
                    <select id="hours" name="hours" class="form-select">
                        @foreach([6, 12, 24, 48, 72, 168] as $hours)
                            <option value="{{ $hours }}" @selected($selectedHours === $hours)>{{ $hours }}h</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Actualiser</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">Requests</div><div class="fs-4 fw-semibold">{{ $summary['requests_profiled'] ?? 0 }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">Slow requests</div><div class="fs-4 fw-semibold">{{ $summary['slow_requests'] ?? 0 }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">Budget breaches</div><div class="fs-4 fw-semibold">{{ $summary['budget_breaches'] ?? 0 }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">Slow queries</div><div class="fs-4 fw-semibold">{{ $summary['slow_queries'] ?? 0 }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">Long jobs</div><div class="fs-4 fw-semibold">{{ $summary['long_jobs'] ?? 0 }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">Fenetre</div><div class="fs-4 fw-semibold">{{ $report['window_hours'] ?? 24 }}h</div></div></div></div>
    </div>

    <x-admin.crud.table-card title="Budgets de performance" :count="count($report['budgets'] ?? [])" :empty-colspan="8" empty-message="Aucun budget defini.">
        <x-slot:head>
            <tr><th>Zone</th><th>Hits</th><th>Avg ms</th><th>Max ms</th><th>Avg queries</th><th>Budget ms</th><th>Budget queries</th><th>Breaches</th></tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach(($report['budgets'] ?? []) as $budget)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $budget['label'] }}</div>
                        <div class="small text-muted">{{ $budget['category'] }}</div>
                    </td>
                    <td>{{ $budget['hits'] }}</td>
                    <td>{{ $budget['avg_response_ms'] }}</td>
                    <td>{{ $budget['max_response_ms_seen'] }}</td>
                    <td>{{ $budget['avg_queries'] }}</td>
                    <td>{{ $budget['target_response_ms'] }} / {{ $budget['max_response_ms'] }}</td>
                    <td>{{ $budget['max_queries'] }}</td>
                    <td>
                        <span class="badge {{ ($budget['breaches'] ?? 0) > 0 ? 'text-bg-warning' : 'text-bg-success' }}">{{ $budget['breaches'] }}</span>
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <x-admin.crud.table-card title="Routes les plus couteuses" :count="count($report['routes'] ?? [])" :empty-colspan="6" empty-message="Aucune route profilee.">
                <x-slot:head>
                    <tr><th>Route</th><th>Hits</th><th>Avg ms</th><th>Max ms</th><th>Avg queries</th><th>Breaches</th></tr>
                </x-slot:head>
                <x-slot:rows>
                    @foreach(($report['routes'] ?? []) as $route)
                        <tr>
                            <td>{{ $route['label'] }}</td>
                            <td>{{ $route['hits'] }}</td>
                            <td>{{ $route['avg_duration_ms'] }}</td>
                            <td>{{ $route['max_duration_ms'] }}</td>
                            <td>{{ $route['avg_queries'] }}</td>
                            <td>{{ $route['budget_breaches'] }}</td>
                        </tr>
                    @endforeach
                </x-slot:rows>
            </x-admin.crud.table-card>
        </div>

        <div class="col-12 col-xl-6">
            <x-admin.crud.table-card title="Requetes lentes dominantes" :count="count($report['slow_queries_top'] ?? [])" :empty-colspan="5" empty-message="Aucune requete lente detectee.">
                <x-slot:head>
                    <tr><th>SQL</th><th>Hits</th><th>Avg ms</th><th>Max ms</th><th>Route</th></tr>
                </x-slot:head>
                <x-slot:rows>
                    @foreach(($report['slow_queries_top'] ?? []) as $query)
                        <tr>
                            <td><span class="small">{{ \Illuminate\Support\Str::limit($query['sql'], 140) }}</span></td>
                            <td>{{ $query['hits'] }}</td>
                            <td>{{ $query['avg_time_ms'] }}</td>
                            <td>{{ $query['max_time_ms'] }}</td>
                            <td>{{ $query['route_name'] ?: $query['path'] ?: 'n/a' }}</td>
                        </tr>
                    @endforeach
                </x-slot:rows>
            </x-admin.crud.table-card>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <x-admin.crud.table-card title="Jobs longs" :count="count($report['long_jobs_top'] ?? [])" :empty-colspan="4" empty-message="Aucun job long detecte.">
                <x-slot:head>
                    <tr><th>Job</th><th>Connection</th><th>Queue</th><th>Duration ms</th></tr>
                </x-slot:head>
                <x-slot:rows>
                    @foreach(($report['long_jobs_top'] ?? []) as $job)
                        <tr>
                            <td>{{ $job['job'] }}</td>
                            <td>{{ $job['connection'] ?: 'n/a' }}</td>
                            <td>{{ $job['queue'] ?: 'n/a' }}</td>
                            <td>{{ $job['duration_ms'] }}</td>
                        </tr>
                    @endforeach
                </x-slot:rows>
            </x-admin.crud.table-card>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Recommandations</h2></div>
                <div class="card-body">
                    <ul class="mb-0 d-grid gap-2">
                        @foreach(($report['recommendations'] ?? []) as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection