@extends('admin.layouts.catmin')

@section('page_title', 'System Check')

@section('content')
<x-admin.crud.page-header
    title="System Check"
    subtitle="Validation environnement + integrite installation + score sante global."
>
    <form method="POST" action="{{ admin_route('system.check.recheck') }}" class="d-inline">
        @csrf
        <button class="btn btn-sm btn-outline-primary" type="submit">
            <i class="bi bi-arrow-repeat me-1"></i>Recheck
        </button>
    </form>
    <a class="btn btn-sm btn-outline-secondary" href="{{ admin_route('system.check', ['format' => 'json']) }}">Export JSON diagnostic</a>
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    @php($badgeClass = ($healthScore['badge'] ?? 'success') === 'danger' ? 'text-bg-danger' : (($healthScore['badge'] ?? 'success') === 'warning' ? 'text-bg-warning' : (($healthScore['badge'] ?? 'success') === 'primary' ? 'text-bg-primary' : 'text-bg-success')))

    @if($diagnostic['blocked'])
        <div class="alert alert-danger">
            <strong>Installation bloquee.</strong>
            Des checks critiques sont en erreur. Corrigez-les avant de continuer.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="small text-muted mb-1">Score global systeme</p>
                    <p class="display-6 mb-2">{{ $healthScore['score'] ?? 100 }}/100</p>
                    <span class="badge {{ $badgeClass }}">{{ $healthScore['label'] ?? 'Stable' }}</span>
                    <p class="small text-muted mt-2 mb-0">Confiance {{ $healthScore['confidence'] ?? 100 }}% · Monitoring {{ strtoupper($monitoring_status) }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Resume checks environnement</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="p-2 rounded bg-light text-center">
                                <div class="small text-muted">OK</div>
                                <div class="h4 mb-0">{{ $diagnostic['summary']['ok'] }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-warning-subtle text-center">
                                <div class="small text-muted">WARNING</div>
                                <div class="h4 mb-0">{{ $diagnostic['summary']['warning'] }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-danger-subtle text-center">
                                <div class="small text-muted">ERROR</div>
                                <div class="h4 mb-0">{{ $diagnostic['summary']['error'] }}</div>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted mt-3 mb-0">Derniere verification: {{ \Illuminate\Support\Carbon::parse($diagnostic['checked_at'])->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-admin.crud.table-card title="System Check items" :count="count($diagnostic['items'] ?? [])" :empty-colspan="6" empty-message="Aucun check disponible.">
        <x-slot:head>
            <tr>
                <th>Item</th>
                <th>Statut</th>
                <th>Message</th>
                <th>Critique</th>
                <th>Recommandation</th>
                <th>Checked at</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($diagnostic['items'] as $item)
                @php($status = (string) ($item['status'] ?? 'OK'))
                @php($rowBadge = $status === 'ERROR' ? 'text-bg-danger' : ($status === 'WARNING' ? 'text-bg-warning' : 'text-bg-success'))
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td><span class="badge {{ $rowBadge }}">{{ $status }}</span></td>
                    <td>{{ $item['message'] }}</td>
                    <td>{{ $item['critical'] ? 'Oui' : 'Non' }}</td>
                    <td>{{ $item['recommendation'] ?: '-' }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($item['checked_at'])->format('d/m/Y H:i:s') }}</td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>

    @if(!empty($diagnostic['recommendations']))
        <div class="card mt-4">
            <div class="card-header bg-white"><h2 class="h6 mb-0">Recommandations correctives</h2></div>
            <div class="list-group list-group-flush">
                @foreach($diagnostic['recommendations'] as $recommendation)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ $recommendation['title'] }}</div>
                        <div class="small text-muted">{{ $recommendation['message'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
