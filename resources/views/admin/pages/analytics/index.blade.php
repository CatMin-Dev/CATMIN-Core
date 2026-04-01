@extends('admin.layouts.catmin')

@section('page_title', 'Analytics internes')

@section('content')
<x-admin.crud.page-header
    title="Analytics internes"
    subtitle="Usage admin/module privacy-safe, distinct de l audit securite et du monitoring technique."
/>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted">Collecte</div>
                    <div class="h5 mb-0">
                        @if($settings['enabled'])
                            <span class="badge text-bg-success">active</span>
                        @else
                            <span class="badge text-bg-secondary">desactivee</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted">Evenements ({{ $report['days'] }}j)</div>
                    <div class="h4 mb-0">{{ number_format((int) ($report['totals']['events'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted">Echecs ({{ $report['days'] }}j)</div>
                    <div class="h4 mb-0">{{ number_format((int) ($report['totals']['failed'] ?? 0)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted">Taux succes</div>
                    <div class="h4 mb-0">{{ $report['totals']['success_rate'] ?? 100 }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Configuration analytics (opt-in)</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.analytics.settings.update') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">Collecte active</label>
                    <select name="enabled" class="form-select">
                        <option value="1" {{ $settings['enabled'] ? 'selected' : '' }}>Oui</option>
                        <option value="0" {{ !$settings['enabled'] ? 'selected' : '' }}>Non</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Mode anonyme</label>
                    <select name="anonymous_mode" class="form-select">
                        <option value="1" {{ $settings['anonymous_mode'] ? 'selected' : '' }}>Oui (recommande)</option>
                        <option value="0" {{ !$settings['anonymous_mode'] ? 'selected' : '' }}>Non</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Retention (jours)</label>
                    <input type="number" name="retention_days" min="7" max="365" class="form-control" value="{{ $settings['retention_days'] }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Domaines suivis</label>
                    <input type="text" name="modules_tracked" class="form-control" value="{{ implode(',', (array) ($settings['modules_tracked'] ?? ['*'])) }}">
                    <div class="form-text">Ex: admin,module,content,docs,ops ou *</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h3 class="h6 mb-0">Modules/domaines les plus utilises</h3></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Domaine</th><th class="text-end">Evenements</th></tr></thead>
                        <tbody>
                        @forelse(($report['top_modules'] ?? []) as $row)
                            <tr><td>{{ $row['domain'] }}</td><td class="text-end">{{ $row['total'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted text-center py-3">Aucune donnee.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h3 class="h6 mb-0">Actions frequentes</h3></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Event</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse(($report['top_actions'] ?? []) as $row)
                            <tr><td>{{ $row['event'] }}</td><td class="text-end">{{ $row['total'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted text-center py-3">Aucune donnee.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h3 class="h6 mb-0">Signaux de friction (warning/failed)</h3></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Event</th><th>Statut</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse(($report['frictions'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['event'] }}</td>
                                <td><span class="badge {{ $row['status'] === 'failed' ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $row['status'] }}</span></td>
                                <td class="text-end">{{ $row['total'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">Aucune donnee.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h3 class="h6 mb-0">Timeline recente</h3></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Jour</th><th class="text-end">Evenements</th></tr></thead>
                        <tbody>
                        @forelse(($report['timeline'] ?? []) as $row)
                            <tr><td>{{ $row['day'] }}</td><td class="text-end">{{ $row['total'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted text-center py-3">Aucune donnee.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
