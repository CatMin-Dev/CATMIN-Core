@extends('admin.layouts.catmin')

@section('page_title', 'Tableau de bord')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Dashboard operationnel</h1>
        <p class="text-muted mb-0">Layout a zones: critique, KPI, activite, actions rapides et widgets addons.</p>
    </div>
    <div class="small text-muted">
        Derniere consolidation: {{ optional($dashboard['generated_at'] ?? null)->format('d/m/Y H:i') }}
    </div>
</header>

<div class="catmin-page-body catmin-dashboard-zones">
    <section class="catmin-zone catmin-zone--critical">
        <div class="catmin-zone__header">
            <h2 class="h5 mb-0">Zone 1 · Etat critique / Health</h2>
        </div>

        @if(!empty($dashboardLayout['alerts']))
            <x-admin.ui.notifications :items="$dashboardLayout['alerts']" :floating="false" />
        @endif

        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h3 class="h6 mb-0">Versioning dashboard</h3>
                        <span class="badge {{ !empty($systemInfo['dashboard_is_up_to_date']) ? 'text-bg-success' : 'text-bg-warning' }}">
                            {{ !empty($systemInfo['dashboard_is_up_to_date']) ? 'Dashboard a jour' : 'Dashboard a verifier' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 small">
                            <div class="col-6 col-xl-3"><div class="text-muted">Version</div><div class="fw-semibold">{{ $systemInfo['dashboard_version'] ?? 'n/a' }}</div></div>
                            <div class="col-6 col-xl-3"><div class="text-muted">Revision git</div><div class="fw-semibold">{{ $systemInfo['revision'] ?? 'n/a' }}</div></div>
                            <div class="col-6 col-xl-3"><div class="text-muted">Environnement</div><div class="fw-semibold">{{ $systemInfo['environment'] ?? 'n/a' }}</div></div>
                            <div class="col-6 col-xl-3"><div class="text-muted">Admin path</div><div class="fw-semibold">/{{ $systemInfo['admin_path'] ?? 'admin' }}</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header bg-white"><h3 class="h6 mb-0">Sante modules critiques</h3></div>
                    <div class="card-body">
                        @foreach(($dashboardLayout['module_health'] ?? []) as $item)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span>{{ $item['label'] ?? 'Module' }}</span>
                                <span class="badge {{ ($item['status'] ?? '') === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item['text'] ?? '-' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($dashboardLayout['zones']['critical']['widgets']))
            <div class="row g-3 mt-1">
                @foreach($dashboardLayout['zones']['critical']['widgets'] as $widget)
                    <div class="col-12 {{ ($widget['span'] ?? 'half') === 'full' ? '' : 'col-xl-6' }}">
                        <article class="card h-100 catmin-widget catmin-widget--{{ $widget['tone'] ?? 'secondary' }}">
                            <div class="card-header bg-white d-flex justify-content-between align-items-start gap-2">
                                <div><h3 class="h6 mb-1">{{ $widget['title'] ?? 'Widget' }}</h3><p class="small text-muted mb-0">{{ $widget['subtitle'] ?? '' }}</p></div>
                            </div>
                            <div class="card-body">
                                @if(empty($widget['items']))
                                    <p class="text-muted mb-0">{{ $widget['empty'] ?? 'Aucune donnee.' }}</p>
                                @else
                                    <ul class="list-unstyled mb-0 d-grid gap-2">
                                        @foreach($widget['items'] as $item)
                                            <li class="catmin-widget-item"><p class="fw-semibold mb-0">{{ $item['primary'] ?? '-' }}</p><p class="small text-muted mb-0">{{ $item['secondary'] ?? '' }}</p></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="catmin-zone catmin-zone--kpis mt-4">
        <div class="catmin-zone__header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Zone 2 · KPIs metier</h2>
            <span class="text-muted small">Priorite visuelle sur les indicateurs</span>
        </div>
        <div class="catmin-kpi-grid">
            @foreach(($dashboardLayout['kpis'] ?? []) as $card)
                @php($canOpen = !empty($card['url']) && (empty($card['permission']) || catmin_can($card['permission'])))
                <article class="card catmin-kpi-card h-100 {{ $canOpen ? 'catmin-kpi-card--link' : '' }}">
                    <div class="card-body d-flex flex-column gap-2">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <p class="text-muted mb-0 small">{{ $card['label'] ?? 'KPI' }}</p>
                            <span class="catmin-kpi-icon"><i class="{{ $card['icon'] ?? 'bi bi-graph-up' }}"></i></span>
                        </div>
                        <p class="catmin-kpi-value mb-0">{{ $card['value'] ?? 0 }}</p>
                        <p class="small text-muted mb-0">{{ $card['description'] ?? '' }}</p>
                        @if($canOpen)<a class="stretched-link" href="{{ $card['url'] }}"></a>@endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="catmin-zone catmin-zone--activity mt-4">
        <div class="catmin-zone__header"><h2 class="h5 mb-0">Zone 3 · Activite recente</h2></div>
        <div class="row g-3">
            <div class="col-12 col-xxl-4">
                <div class="card h-100">
                    <div class="card-header bg-white"><h3 class="h6 mb-0">Contenus publies (7j)</h3></div>
                    <div class="card-body d-grid gap-2">
                        @php($contentSeries = (array) ($dashboardLayout['charts']['content_7d'] ?? []))
                        @php($contentMax = max(1, collect($contentSeries)->map(fn ($row) => (int) (($row['pages'] ?? 0) + ($row['articles'] ?? 0)))->max() ?? 1))
                        @foreach($contentSeries as $row)
                            @php($pages = (int) ($row['pages'] ?? 0))
                            @php($articles = (int) ($row['articles'] ?? 0))
                            <div class="catmin-lite-chart-row">
                                <div class="catmin-lite-chart-label">{{ $row['label'] ?? '-' }}</div>
                                <div class="catmin-lite-chart-track">
                                    <div class="catmin-lite-chart-bar catmin-lite-chart-bar--pages" style="width: {{ round(($pages / $contentMax) * 100, 1) }}%"></div>
                                    <div class="catmin-lite-chart-bar catmin-lite-chart-bar--articles" style="width: {{ round(($articles / $contentMax) * 100, 1) }}%"></div>
                                </div>
                                <div class="catmin-lite-chart-value">{{ $pages + $articles }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-4">
                <div class="card h-100">
                    <div class="card-header bg-white"><h3 class="h6 mb-0">Incidents (7j)</h3></div>
                    <div class="card-body d-grid gap-2">
                        @php($incidentSeries = (array) ($dashboardLayout['charts']['incidents_7d'] ?? []))
                        @php($incidentMax = max(1, collect($incidentSeries)->max('count') ?? 1))
                        @foreach($incidentSeries as $row)
                            @php($count = (int) ($row['count'] ?? 0))
                            <div class="catmin-lite-chart-row">
                                <div class="catmin-lite-chart-label">{{ $row['label'] ?? '-' }}</div>
                                <div class="catmin-lite-chart-track"><div class="catmin-lite-chart-bar catmin-lite-chart-bar--incidents" style="width: {{ round(($count / $incidentMax) * 100, 1) }}%"></div></div>
                                <div class="catmin-lite-chart-value">{{ $count }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-4">
                <div class="card h-100">
                    <div class="card-header bg-white"><h3 class="h6 mb-0">Performance (12h)</h3></div>
                    <div class="card-body d-grid gap-2">
                        @php($perfSeries = (array) ($dashboardLayout['charts']['perf_12h'] ?? []))
                        @foreach($perfSeries as $row)
                            @php($score = max(0, min(100, (int) ($row['score'] ?? 0))))
                            <div class="catmin-lite-chart-row">
                                <div class="catmin-lite-chart-label">{{ $row['label'] ?? '-' }}</div>
                                <div class="catmin-lite-chart-track"><div class="catmin-lite-chart-bar catmin-lite-chart-bar--perf" style="width: {{ $score }}%"></div></div>
                                <div class="catmin-lite-chart-value">{{ $score }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="catmin-zone catmin-zone--actions mt-4">
        <div class="catmin-zone__header"><h2 class="h5 mb-0">Zone 4 · Actions rapides</h2></div>
        <div class="card">
            <div class="card-body">
                <div class="catmin-quick-actions">
                    @foreach(($dashboardLayout['quick_actions'] ?? []) as $action)
                        @continue(empty($action['url']))
                        @continue(!empty($action['permission']) && !catmin_can($action['permission']))
                        <a class="btn btn-outline-secondary" href="{{ $action['url'] }}"><i class="{{ $action['icon'] ?? 'bi bi-arrow-right-circle' }} me-1"></i>{{ $action['label'] ?? 'Action' }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="catmin-zone catmin-zone--secondary mt-4">
        <div class="catmin-zone__header"><h2 class="h5 mb-0">Zone 5 · Widgets addons secondaires</h2></div>
        <div class="row g-3">
            @foreach(($dashboardLayout['zones']['secondary']['widgets'] ?? []) as $widget)
                <div class="col-12 {{ ($widget['span'] ?? 'half') === 'full' ? '' : 'col-xxl-6' }}">
                    <article class="card h-100 catmin-widget catmin-widget--{{ $widget['tone'] ?? 'secondary' }}">
                        <div class="card-header bg-white d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h3 class="h6 mb-1">{{ $widget['title'] ?? 'Widget' }}</h3>
                                @if(!empty($widget['subtitle']))<p class="small text-muted mb-0">{{ $widget['subtitle'] }}</p>@endif
                            </div>
                            @if(!empty($widget['action']['url']) && (empty($widget['action']['permission']) || catmin_can($widget['action']['permission'])))
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $widget['action']['url'] }}">{{ $widget['action']['label'] ?? 'Ouvrir' }}</a>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(empty($widget['items']))
                                <p class="text-muted mb-0">{{ $widget['empty'] ?? 'Aucune donnee.' }}</p>
                            @else
                                <ul class="list-unstyled mb-0 d-grid gap-2">
                                    @foreach($widget['items'] as $item)
                                        <li class="catmin-widget-item">
                                            <p class="fw-semibold mb-0">{{ $item['primary'] ?? '-' }}</p>
                                            <p class="small text-muted mb-0">{{ $item['secondary'] ?? '' }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
