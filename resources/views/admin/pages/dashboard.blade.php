@extends('admin.layouts.catmin')

@section('page_title', 'Tableau de bord')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Dashboard principal CATMIN</h1>
        <p class="text-muted mb-0">Vue d'ensemble des activites, incidents et priorites operationnelles.</p>
    </div>
    <div class="small text-muted">
        Derniere consolidation: {{ optional($dashboard['generated_at'] ?? null)->format('d/m/Y H:i') }}
    </div>
</header>

<div class="catmin-page-body">
    <section class="mb-4">
        <div class="card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0">Versioning dashboard</h2>
                <span class="badge {{ !empty($systemInfo['dashboard_is_up_to_date']) ? 'text-bg-success' : 'text-bg-warning' }}">
                    {{ !empty($systemInfo['dashboard_is_up_to_date']) ? 'Dashboard a jour' : 'Dashboard a verifier' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Version dashboard</div>
                        <div class="fw-semibold">{{ $systemInfo['dashboard_version'] ?? 'n/a' }}</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Version attendue (phase)</div>
                        <div class="fw-semibold">{{ $systemInfo['expected_dashboard_version'] ?? 'n/a' }}</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Revision git</div>
                        <div class="fw-semibold">{{ $systemInfo['revision'] ?? 'n/a' }}</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Branche</div>
                        <div class="fw-semibold">
                            {{ $systemInfo['branch'] ?? 'n/a' }}
                            @if(!empty($systemInfo['is_dirty']))
                                <span class="badge text-bg-warning ms-1">dirty</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Laravel</div>
                        <div class="fw-semibold">{{ $systemInfo['laravel_version'] ?? 'n/a' }}</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">PHP</div>
                        <div class="fw-semibold">{{ $systemInfo['php_version'] ?? 'n/a' }}</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Environnement</div>
                        <div class="fw-semibold">{{ $systemInfo['environment'] ?? 'n/a' }}</div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted">Admin path</div>
                        <div class="fw-semibold">/{{ $systemInfo['admin_path'] ?? 'admin' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($dashboard['alerts']))
        <x-admin.ui.notifications :items="$dashboard['alerts']" :floating="true" />
    @endif

    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Indicateurs cles</h2>
            <span class="text-muted small">Cliquez un indicateur pour agir</span>
        </div>
        <div class="catmin-kpi-grid">
            @foreach(($dashboard['kpis'] ?? []) as $card)
                @php($canOpen = !empty($card['url']) && (empty($card['permission']) || catmin_can($card['permission'])))
                <article class="card catmin-kpi-card h-100 {{ $canOpen ? 'catmin-kpi-card--link' : '' }}">
                    <div class="card-body d-flex flex-column gap-2">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <p class="text-muted mb-0 small">{{ $card['label'] ?? 'KPI' }}</p>
                            <span class="catmin-kpi-icon"><i class="{{ $card['icon'] ?? 'bi bi-graph-up' }}"></i></span>
                        </div>
                        <p class="catmin-kpi-value mb-0">{{ $card['value'] ?? 0 }}</p>
                        <p class="small text-muted mb-0">{{ $card['description'] ?? '' }}</p>
                        @if($canOpen)
                            <a class="stretched-link" href="{{ $card['url'] }}" aria-label="Ouvrir {{ $card['label'] ?? 'kpi' }}"></a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Actions rapides</h2>
                </div>
                <div class="card-body">
                    <div class="catmin-quick-actions">
                        @foreach(($dashboard['quick_actions'] ?? []) as $action)
                            @continue(empty($action['url']))
                            @continue(!empty($action['permission']) && !catmin_can($action['permission']))
                            <a class="btn btn-outline-secondary" href="{{ $action['url'] }}">
                                <i class="{{ $action['icon'] ?? 'bi bi-arrow-right-circle' }} me-1"></i>{{ $action['label'] ?? 'Action' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Sante modules critiques</h2>
                </div>
                <div class="card-body">
                    @foreach(($dashboard['module_health'] ?? []) as $item)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span>{{ $item['label'] ?? 'Module' }}</span>
                            <span class="badge {{ ($item['status'] ?? '') === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $item['text'] ?? '-' }}
                            </span>
                        </div>
                    @endforeach
                    <div class="small text-muted mt-3">Les modules inactifs n'injectent pas leurs widgets dashboard.</div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h5 mb-0">Widgets operationnels</h2>
            <span class="text-muted small">Injectables par modules via registre dashboard</span>
        </div>

        <div class="row g-3">
            @foreach($dashboardWidgets as $widget)
                <div class="col-12 col-xxl-6">
                    <article class="card h-100 catmin-widget catmin-widget--{{ $widget['tone'] ?? 'secondary' }}">
                        <div class="card-header bg-white d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h3 class="h6 mb-1">{{ $widget['title'] ?? 'Widget' }}</h3>
                                @if(!empty($widget['subtitle']))
                                    <p class="small text-muted mb-0">{{ $widget['subtitle'] }}</p>
                                @endif
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
                                            @if(!empty($item['meta']))
                                                <span class="small text-muted">{{ $item['meta'] }}</span>
                                            @endif
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
