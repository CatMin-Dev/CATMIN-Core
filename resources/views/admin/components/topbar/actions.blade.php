<div class="catmin-topbar-actions d-flex align-items-center gap-2">
    @foreach($actions as $action)
        <a class="btn btn-sm {{ $action['variant'] ?? 'btn-outline-secondary' }}" href="{{ $action['url'] ?? '#' }}">
            @if(!empty($action['icon']))<i class="{{ $action['icon'] }} me-1"></i>@endif
            {{ $action['label'] ?? 'Action' }}
        </a>
    @endforeach
</div>
