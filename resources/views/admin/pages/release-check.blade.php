@extends('admin.layouts.catmin')

@section('page_title', 'Controle de release')

@section('content')
<header class="catmin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1 class="h3 mb-1">Release Check — Gate de validation pre-deployment</h1>
        <p class="text-muted mb-0">Verifiez que tous les criteres de release sont satisfaits avant le deploiement en production.</p>
    </div>
    <div>
        <span class="badge {{ $isReady ? 'text-bg-success' : 'text-bg-danger' }} fs-6">
            {{ $status }}
        </span>
    </div>
</header>

<div class="catmin-page-body">
    <!-- Status Summary -->
    <section class="mb-4">
        <div class="card {{ $isReady ? 'border-success' : 'border-danger' }}">
            <div class="card-header {{ $isReady ? 'bg-success-subtle' : 'bg-danger-subtle' }} d-flex align-items-center justify-content-between">
                <h2 class="h6 mb-0">Synthese</h2>
                <span class="badge {{ $isReady ? 'text-bg-success' : 'text-bg-danger' }}">
                    {{ $isReady ? 'Pret pour release' : count($blockers) . ' bloquant(s)' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted small">Verifications automatisees</div>
                        <div class="fs-5 fw-semibold">
                            {{ $summary['automated_ok'] ?? 0 }} / {{ $summary['automated_total'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted small">Verifications manuelles</div>
                        <div class="fs-5 fw-semibold">
                            {{ $summary['manual_passed'] ?? 0 }} / {{ $summary['manual_total'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted small">Items en attente</div>
                        <div class="fs-5 fw-semibold">
                            {{ $summary['manual_pending'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="text-muted small">Generee</div>
                        <div class="small text-muted">
                            {{ \Carbon\Carbon::parse($generatedAt)->format('d/m H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blockers List -->
    @if(count($blockers) > 0)
        <section class="mb-4">
            <div class="card border-danger">
                <div class="card-header bg-danger-subtle">
                    <h2 class="h6 mb-0">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                        Criteres bloquants ({{ count($blockers) }})
                    </h2>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($blockers as $blocker)
                            <li class="list-group-item d-flex justify-content-between align-items-start p-3">
                                <div>
                                    <p class="mb-0 fw-semibold">{{ $blocker }}</p>
                                </div>
                                <span class="badge bg-danger">Non resolu</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif

    <!-- Checks Details -->
    @if(!empty($sections['automated_tests']['checks']))
        <section class="mb-4">
            <h2 class="h5 mb-3">Verifications automatisees</h2>
            <div class="row g-3">
                @foreach($sections['automated_tests']['checks'] as $check)
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h3 class="h6 mb-0">{{ $check['label'] ?? 'Check' }}</h3>
                                    @php
                                        $checkOk = (bool)($check['ok'] ?? false);
                                        $severity = (string)($check['severity'] ?? 'warning');
                                    @endphp
                                    <span class="badge {{ $checkOk ? 'bg-success' : ($severity === 'critical' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $checkOk ? 'OK' : ($severity === 'critical' ? 'CRITIQUE' : 'AVERTISSEMENT') }}
                                    </span>
                                </div>
                                <p class="text-muted small mb-0">
                                    {{ $check['details'] ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- V2 Checklist -->
    @if(!empty($sections['v2_checklist']))
        <section class="mb-4">
            <h2 class="h5 mb-3">Checklist V2 integrite technique</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Verification</th>
                            <th>Details</th>
                            <th class="text-end">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sections['v2_checklist'] as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item['label'] ?? 'Item' }}</td>
                                <td class="small text-muted">{{ $item['details'] ?? '' }}</td>
                                <td class="text-end">
                                    <span class="badge {{ ($item['ok'] ?? false) ? 'bg-success' : 'bg-warning' }}">
                                        {{ ($item['ok'] ?? false) ? 'OK' : 'À VERIFIER' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <!-- Release Criteria -->
    @if(!empty($sections['release_criteria']))
        <section class="mb-4">
            <h2 class="h5 mb-3">Criteres de release</h2>
            <div class="row g-3">
                @foreach($sections['release_criteria'] as $criterion)
                    <div class="col-12 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="h6">{{ $criterion['name'] ?? 'Criterion' }}</h3>
                                        <p class="small text-muted mb-0">{{ $criterion['description'] ?? '' }}</p>
                                    </div>
                                    <span class="badge {{ ($criterion['ok'] ?? false) ? 'bg-success' : 'bg-warning' }}">
                                        {{ ($criterion['ok'] ?? false) ? 'OK' : 'ATTENTION' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Security Validation -->
    @if(!empty($sections['security_validation']))
        <section class="mb-4">
            <h2 class="h5 mb-3">Validation securite</h2>
            <div class="alert alert-info small" role="alert">
                <strong>Guardrails securite:</strong> Tous les criteres critiques doivent etre satisfaits avant release en production.
            </div>
            <div class="row g-3">
                @foreach($sections['security_validation'] as $check)
                    @php
                        $checkOk = (bool)($check['ok'] ?? false);
                        $severity = (string)($check['severity'] ?? 'warning');
                    @endphp
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h3 class="h6 mb-0">{{ $check['name'] ?? 'Security Check' }}</h3>
                                    <span class="badge {{ $checkOk ? 'bg-success' : ($severity === 'critical' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $checkOk ? 'PASS' : 'FAIL' }}
                                    </span>
                                </div>
                                <p class="text-muted small mb-0">{{ $check['description'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
