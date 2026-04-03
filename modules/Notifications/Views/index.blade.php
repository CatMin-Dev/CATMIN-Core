@extends('admin.layouts.catmin')

@section('page_title', 'Notifications')

@section('content')
<x-admin.crud.page-header
    title="Centre de notifications"
    subtitle="Alertes système, incidents critiques et événements importants."
>
    @if(catmin_can('module.notifications.manage'))
        <form method="POST" action="{{ admin_route('notifications.aggregate') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-repeat me-1"></i>Agréger maintenant
            </button>
        </form>
    @endif
    @if(catmin_can('module.notifications.read') && $stats['unread'] > 0)
        <form method="POST" action="{{ admin_route('notifications.read-all') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-check2-all me-1"></i>Tout marquer lu
            </button>
        </form>
    @endif
</x-admin.crud.page-header>

<div class="catmin-page-body">
    <x-admin.crud.flash-messages />

    {{-- Stats KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card h-100 {{ $stats['critical'] > 0 ? 'border-danger' : '' }}">
                <div class="card-body">
                    <div class="small text-muted">Critiques non lues</div>
                    <div class="fs-4 fw-semibold {{ $stats['critical'] > 0 ? 'text-danger' : '' }}">{{ $stats['critical'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 {{ $stats['warning'] > 0 ? 'border-warning' : '' }}">
                <div class="card-body">
                    <div class="small text-muted">Avertissements non lus</div>
                    <div class="fs-4 fw-semibold {{ $stats['warning'] > 0 ? 'text-warning' : '' }}">{{ $stats['warning'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="small text-muted">Total non lues</div>
                    <div class="fs-4 fw-semibold">{{ $stats['unread'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 {{ $stats['unacknowledged'] > 0 ? 'border-danger' : '' }}">
                <div class="card-body">
                    <div class="small text-muted">Critiques non acquittées</div>
                    <div class="fs-4 fw-semibold {{ $stats['unacknowledged'] > 0 ? 'text-danger' : '' }}">{{ $stats['unacknowledged'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ admin_route('notifications.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label" for="filter-type">Type</label>
                    <select id="filter-type" name="type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach(['critical', 'warning', 'info', 'success'] as $t)
                            <option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="filter-source">Source</label>
                    <select id="filter-source" name="source" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach($sources as $src)
                            <option value="{{ $src }}" @selected(($filters['source'] ?? '') === $src)>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="filter-read">Statut lecture</label>
                    <select id="filter-read" name="read" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="unread" @selected(($filters['read'] ?? '') === 'unread')>Non lues</option>
                        <option value="read" @selected(($filters['read'] ?? '') === 'read')>Lues</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filter-critical" name="critical_only" value="1" @checked(!empty($filters['critical_only']))>
                        <label class="form-check-label small" for="filter-critical">Critiques seulement</label>
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ admin_route('notifications.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk form + listing --}}
    <form id="bulk-form" method="POST" action="{{ admin_route('notifications.bulk') }}">
        @csrf
        <x-admin.crud.table-card
            title="Notifications"
            :count="$notifications->total()"
            :empty-colspan="7"
            empty-message="Aucune notification."
        >
            <x-slot:head>
                <tr>
                    <th style="width:28px"><input type="checkbox" class="form-check-input" id="select-all" title="Tout sélectionner"></th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </x-slot:head>
            <x-slot:rows>
                @foreach($notifications as $notif)
                    <tr class="{{ !$notif->is_read ? 'fw-semibold' : '' }}">
                        <td><input type="checkbox" name="ids[]" value="{{ $notif->id }}" class="form-check-input row-checkbox"></td>
                        <td>
                            @php
                                $badge = match($notif->type) {
                                    'critical' => 'danger',
                                    'warning' => 'warning',
                                    'success' => 'success',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $notif->type }}</span>
                        </td>
                        <td><span class="badge text-bg-light text-dark">{{ $notif->source ?? '—' }}</span></td>
                        <td>
                            <div>{{ $notif->title }}</div>
                            <div class="small text-muted">{{ Str::limit($notif->message, 80) }}</div>
                            @if($notif->action_url)
                                <a href="{{ $notif->action_url }}" class="small text-primary">{{ $notif->action_label ?? 'Voir' }} →</a>
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap">{{ $notif->created_at?->diffForHumans() }}</td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                @if(!$notif->is_read)
                                    <span class="badge text-bg-primary">Non lu</span>
                                @else
                                    <span class="badge text-bg-light text-muted">Lu</span>
                                @endif
                                @if($notif->is_acknowledged)
                                    <span class="badge text-bg-success">Acquitté</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @if(!$notif->is_read && catmin_can('module.notifications.read'))
                                    <form method="POST" action="{{ admin_route('notifications.read', $notif) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-primary" title="Marquer lu">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </form>
                                @endif
                                @if(!$notif->is_acknowledged && catmin_can('module.notifications.acknowledge'))
                                    <form method="POST" action="{{ admin_route('notifications.acknowledge', $notif) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-secondary" title="Acquitter">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-slot:rows>
        </x-admin.crud.table-card>

        {{-- Bulk actions --}}
        @if(catmin_can('module.notifications.read'))
            <div class="d-flex align-items-center gap-2 mt-2">
                <select name="bulk_action" class="form-select form-select-sm w-auto">
                    <option value="read">Marquer lu</option>
                    <option value="acknowledge">Acquitter</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Appliquer</button>
            </div>
        @endif
    </form>

    <div class="mt-3">
        {{ $notifications->withQueryString()->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
