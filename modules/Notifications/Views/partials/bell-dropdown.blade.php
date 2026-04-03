@php
use App\Services\Notifications\AdminNotificationService;
$_notifCount = AdminNotificationService::unreadCount();
$_notifCritical = AdminNotificationService::unreadCriticalCount();
$_notifList = AdminNotificationService::latestForDropdown(6);
@endphp

<div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm position-relative" type="button"
            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
        <i class="bi bi-bell{{ $_notifCritical > 0 ? '-fill text-danger' : '' }}"></i>
        @if($_notifCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill {{ $_notifCritical > 0 ? 'text-bg-danger' : 'text-bg-primary' }}">
                {{ $_notifCount > 99 ? '99+' : $_notifCount }}
            </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow" style="min-width:320px; max-width:380px;">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="fw-semibold small">Notifications</span>
            @if($_notifCount > 0)
                <span class="badge text-bg-secondary">{{ $_notifCount }} non {{ $_notifCount > 1 ? 'lues' : 'lue' }}</span>
            @endif
        </div>

        @if(count($_notifList) === 0)
            <div class="dropdown-item-text text-muted small py-3 text-center">
                <i class="bi bi-check2-circle me-1"></i>Aucune notification
            </div>
        @else
            <div style="max-height:320px; overflow-y:auto;">
                @foreach($_notifList as $n)
                    @php
                        $color = match($n->type) {
                            'critical' => 'danger',
                            'warning' => 'warning',
                            'success' => 'success',
                            default => 'secondary',
                        };
                    @endphp
                    <div class="px-3 py-2 border-bottom {{ !$n->is_read ? 'bg-light' : '' }}" style="font-size:0.85rem;">
                        <div class="d-flex align-items-start gap-2">
                            <span class="badge text-bg-{{ $color }} flex-shrink-0 mt-1">{{ $n->type }}</span>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-semibold text-truncate">{{ $n->title }}</div>
                                <div class="text-muted small">{{ Str::limit($n->message, 60) }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $n->created_at?->diffForHumans() }}</div>
                            </div>
                        </div>
                        @if($n->action_url)
                            <a href="{{ $n->action_url }}" class="small text-primary d-block mt-1">{{ $n->action_label ?? 'Voir' }} →</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center">
            @if(Route::has('admin.notifications.index'))
                <a class="btn btn-xs btn-outline-primary btn-sm" href="{{ admin_route('notifications.index') }}">Voir tout</a>
            @endif
            @if($_notifCount > 0 && Route::has('admin.notifications.read-all'))
                <form method="POST" action="{{ admin_route('notifications.read-all') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-link btn-sm p-0 text-muted small">Tout marquer lu</button>
                </form>
            @endif
        </div>
    </div>
</div>
