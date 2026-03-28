@php
    // Load RBAC matrix if available
    $rbacPath = storage_path('logs/rbac-matrix.json');
    $rbacData = [];
    
    if (file_exists($rbacPath)) {
        $rbacData = json_decode(file_get_contents($rbacPath), true) ?? [];
    }
    
    $summary = $rbacData['summary'] ?? [
        'total_routes' => 0,
        'with_permission' => 0,
        'without_permission' => 0,
        'coverage_percentage' => 0
    ];
    
    $coverage = $summary['coverage_percentage'] ?? 0;
    $coverageColor = match(true) {
        $coverage >= 90 => 'success',
        $coverage >= 75 => 'warning',
        default => 'danger'
    };
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-gradient-info text-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-shield-alt"></i> RBAC Coverage
            </h5>
            <small class="opacity-75">Authorization Status</small>
        </div>
    </div>

    <div class="card-body">
        @if(!empty($rbacData))
            <!-- Coverage Progress -->
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-500">Protected Routes</span>
                    <span class="badge badge-{{ $coverageColor }}">{{ $coverage }}%</span>
                </div>
                <div class="progress" style="height: 25px;">
                    <div 
                        class="progress-bar bg-{{ $coverageColor }}" 
                        role="progressbar" 
                        style="width: {{ $coverage }}%"
                        aria-valuenow="{{ $coverage }}" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        {{ $coverage }}%
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-box bg-light p-3 rounded text-center">
                        <div class="small text-muted mb-1">Total Routes</div>
                        <div class="h4 mb-0 fw-bold text-primary">{{ $summary['total_routes'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-box bg-light p-3 rounded text-center">
                        <div class="small text-muted mb-1">Protected</div>
                        <div class="h4 mb-0 fw-bold text-success">{{ $summary['with_permission'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Unprotected Routes Alert -->
            @if($summary['without_permission'] > 0)
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>{{ $summary['without_permission'] }} unprotected routes</strong>
                    <div class="small mt-2">
                        Most are intentionally public (auth/errors). Review full audit in Docs → RBAC.
                    </div>
                </div>
            @else
                <div class="alert alert-success mb-0" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <strong>All routes protected!</strong>
                </div>
            @endif
        @else
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle"></i>
                <strong>Run audit to generate RBAC matrix:</strong>
                <div class="small mt-2 font-monospace">
                    php artisan audit:rbac --output=storage/logs/rbac-matrix.json
                </div>
            </div>
        @endif

        <!-- Footer Actions -->
        <div class="mt-4 pt-3 border-top">
            <a href="{{ route('admin.docs.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-book"></i> View Full RBAC Report
            </a>
        </div>
    </div>
</div>

<style>
    .bg-gradient-info {
        background: linear-gradient(135deg, #0dd8ea 0%, #0c9fe6 100%);
    }

    .stat-box {
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }

    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .fw-500 {
        font-weight: 500;
    }

    .opacity-75 {
        opacity: 0.75;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
    }
</style>
