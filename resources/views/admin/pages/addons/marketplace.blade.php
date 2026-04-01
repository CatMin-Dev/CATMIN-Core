@extends('admin.layouts.catmin')

@section('page_title', 'Marketplace Addons')

@section('content')
<x-admin.crud.page-header
    title="Marketplace Addons"
    subtitle="Base interne de distribution des addons telechargeables et versionnes."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Packages disponibles</div>
                    <div class="h3 mb-0">{{ number_format((int) ($index['packages_count'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Addons installes</div>
                    <div class="h3 mb-0">{{ number_format((int) (($index['installed_addons']['total'] ?? 0))) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Index genere</div>
                    <div class="small">{{ $index['generated_at'] ?? 'n/a' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 mb-0">Registry addons distribues</h2>
                <div class="small text-muted">Catalogue local pret pour usage prive ou registre futur.</div>
            </div>
            <form method="post" action="{{ route('admin.addons.marketplace.rebuild') }}">
                @csrf
                <button class="btn btn-sm btn-outline-primary" type="submit">
                    <i class="bi bi-arrow-repeat me-1"></i>Rebuild index
                </button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Addon</th>
                        <th>Version</th>
                        <th>Compatibilite</th>
                        <th>Dependances</th>
                        <th>Integrite</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($index['packages'] ?? []) as $pkg)
                        @php
                            $compat = $pkg['compatibility'] ?? [];
                            $status = (string) ($compat['status'] ?? 'incompatible');
                            $badgeClass = $status === 'compatible'
                                ? 'text-bg-success'
                                : ($status === 'compatible_with_warnings' ? 'text-bg-warning' : 'text-bg-danger');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $pkg['name'] ?? ($pkg['slug'] ?? 'Addon') }}</div>
                                <div class="small text-muted">{{ $pkg['description'] ?? '' }}</div>
                                <div class="small text-muted">{{ $pkg['author'] ?? 'n/a' }} · {{ $pkg['category'] ?? 'general' }}</div>
                            </td>
                            <td>
                                <div><span class="badge text-bg-light text-dark">{{ $pkg['version'] ?? 'n/a' }}</span></div>
                                @if(!empty($pkg['installed_version']))
                                    <div class="small text-muted">installe: {{ $pkg['installed_version'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div><span class="badge {{ $badgeClass }}">{{ $compat['summary'] ?? 'n/a' }}</span></div>
                                @if(!empty($compat['blockers']))
                                    <div class="small text-danger mt-1">{{ implode(' ', $compat['blockers']) }}</div>
                                @elseif(!empty($compat['warnings']))
                                    <div class="small text-warning mt-1">{{ implode(' ', $compat['warnings']) }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small"><strong>modules:</strong> {{ empty($pkg['required_modules']) ? 'aucun' : implode(', ', $pkg['required_modules']) }}</div>
                                <div class="small"><strong>addons:</strong> {{ empty($pkg['dependencies']) ? 'aucun' : implode(', ', $pkg['dependencies']) }}</div>
                            </td>
                            <td>
                                <div class="small font-monospace">{{ \Illuminate\Support\Str::limit((string) ($pkg['sha256'] ?? ''), 20) }}</div>
                                <div class="small {{ !empty($pkg['package_valid']) ? 'text-success' : 'text-danger' }}">{{ !empty($pkg['package_valid']) ? 'package valide' : 'package bloque' }}</div>
                            </td>
                            <td>
                                @if(!empty($pkg['installed']))
                                    <span class="badge {{ !empty($pkg['enabled']) ? 'text-bg-success' : 'text-bg-secondary' }}">{{ !empty($pkg['enabled']) ? 'active' : 'installee' }}</span>
                                    @if(!empty($pkg['update_available']))
                                        <div class="small text-warning mt-1">update disponible</div>
                                    @endif
                                @else
                                    <span class="badge text-bg-light text-dark">non installee</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    @if(empty($pkg['installed']) || !empty($pkg['update_available']))
                                        <form method="post" action="{{ route('admin.addons.marketplace.install') }}">
                                            @csrf
                                            <input type="hidden" name="package_file" value="{{ $pkg['package_file'] ?? '' }}">
                                            <button class="btn btn-sm btn-primary" type="submit" {{ empty($pkg['package_valid']) ? 'disabled' : '' }}>
                                                {{ empty($pkg['installed']) ? 'Installer' : 'Mettre a jour' }}
                                            </button>
                                        </form>
                                    @endif

                                    @if(!empty($pkg['installed']) && empty($pkg['enabled']))
                                        <form method="post" action="{{ route('admin.addons.marketplace.enable') }}">
                                            @csrf
                                            <input type="hidden" name="slug" value="{{ $pkg['slug'] ?? '' }}">
                                            <button class="btn btn-sm btn-outline-success" type="submit">Activer</button>
                                        </form>
                                    @endif

                                    @if(!empty($pkg['installed']) && !empty($pkg['enabled']))
                                        <form method="post" action="{{ route('admin.addons.marketplace.disable') }}">
                                            @csrf
                                            <input type="hidden" name="slug" value="{{ $pkg['slug'] ?? '' }}">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Desactiver</button>
                                        </form>
                                    @endif

                                    @if(!empty($pkg['docs_url']))
                                        <a href="{{ $pkg['docs_url'] }}" class="btn btn-sm btn-outline-dark" target="_blank" rel="noreferrer">Voir doc</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucun package zip detecte dans storage/app/addons/packages.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
