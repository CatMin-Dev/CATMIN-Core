<div class="catmin-nav-flyout" data-flyout-for="{{ $master['id'] }}" aria-hidden="true">
    <div class="catmin-nav-flyout__panel">
        <div class="catmin-nav-flyout__title">{{ $master['label'] }}</div>
        @foreach($master['children'] as $sub)
            <div class="catmin-nav-flyout__group">
                <div class="catmin-nav-flyout__group-title">{{ $sub['label'] }}</div>
                <div class="d-grid gap-1">
                    @foreach($sub['children'] as $item)
                        @include('admin.components.navigation.sidebar-item', ['item' => $item])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
