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
            <h2 class="h6 mb-0">Packages addons (zip)</h2>
            <form method="post" action="{{ route('admin.addons.marketplace.rebuild') }}">
                @csrf
                <button class="btn btn-sm btn-outline-primary" type="submit">
                    <i class="bi bi-arrow-repeat me-1"></i>Rebuild index
                </button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Addon</th>
                        <th>Version</th>
                        <th>Build</th>
                        <th>Fichier</th>
                        <th>Taille</th>
                        <th>SHA-256</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($index['packages'] ?? []) as $pkg)
                        <tr>
                            <td>{{ $pkg['slug'] ?? 'n/a' }}</td>
                            <td><span class="badge text-bg-light text-dark">{{ $pkg['version'] ?? 'n/a' }}</span></td>
                            <td>{{ $pkg['build'] ?: 'n/a' }}</td>
                            <td><code>{{ $pkg['file'] ?? 'n/a' }}</code></td>
                            <td>{{ number_format(((int) ($pkg['size_bytes'] ?? 0)) / 1024, 1) }} KB</td>
                            <td><code class="small">{{ \Illuminate\Support\Str::limit((string) ($pkg['sha256'] ?? ''), 18) }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun package zip detecte dans storage/app/addons/packages.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
