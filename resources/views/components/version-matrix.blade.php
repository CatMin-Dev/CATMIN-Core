@php
    $matrix = \App\Services\ModuleVersionManager::generateMatrix();
    $modules = $matrix['modules'] ?? [];
    $v2DevModules = array_filter($modules, fn($v) => str_contains($v, '-dev'));
    $stableModules = array_filter($modules, fn($v) => !str_contains($v, '-dev'));
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-gradient-primary text-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-code-branch"></i> System Versions
            </h5>
            <small class="opacity-75">{{ $matrix['development_phase'] }}</small>
        </div>
    </div>

    <div class="card-body">
        <!-- Dashboard Version -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge badge-primary me-2">Dashboard</span>
                    <code class="fs-6{{ str_contains($matrix['dashboard_version'], '-') ? ' fw-bold' : '' }}">
                        {{ $matrix['dashboard_version'] }}
                    </code>
                </div>
            </div>
            <div class="col-md-6 text-muted text-end">
                <small>
                    <i class="fas fa-calendar"></i> {{ $matrix['generated_at'] }}
                </small>
            </div>
        </div>

        <!-- Module Versions Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link active" 
                    id="v2-tab" 
                    data-bs-toggle="tab" 
                    data-bs-target="#v2-pane" 
                    type="button">
                    <span class="badge badge-warning me-2">{{ count($v2DevModules) }}</span>
                    V2-DEV Modules
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link" 
                    id="stable-tab" 
                    data-bs-toggle="tab" 
                    data-bs-target="#stable-pane" 
                    type="button">
                    <span class="badge badge-success me-2">{{ count($stableModules) }}</span>
                    Stable (V1.x)
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- V2-DEV Modules -->
            <div class="tab-pane fade show active" id="v2-pane" role="tabpanel">
                @forelse($v2DevModules as $module => $version)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="fw-500 text-capitalize">{{ ucfirst($module) }}</span>
                        <span class="badge badge-info">{{ $version }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No V2-DEV modules</p>
                @endforelse
            </div>

            <!-- Stable Modules -->
            <div class="tab-pane fade" id="stable-pane" role="tabpanel">
                @forelse($stableModules as $module => $version)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="fw-500 text-capitalize">{{ ucfirst($module) }}</span>
                        <span class="badge badge-success">{{ $version }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No stable modules</p>
                @endforelse
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="mt-4 pt-3 border-top">
            <div class="row g-2">
                <div class="col-auto">
                    <a href="{{ url('/admin/modules') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-box"></i> Modules
                    </a>
                </div>
                <div class="col-auto">
                    <button 
                        class="btn btn-sm btn-outline-secondary"
                        onclick="location.href='{{ route('admin.docs.index') }}'" 
                        title="View documentation">
                        <i class="fas fa-book"></i> Docs
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Total: {{ $matrix['total_modules'] }} modules
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .badge-info {
        background-color: #17a2b8;
    }

    .fw-500 {
        font-weight: 500;
    }

    .opacity-75 {
        opacity: 0.75;
    }
</style>
