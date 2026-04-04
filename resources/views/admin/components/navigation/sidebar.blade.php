@php($tree = (array) ($viewModel['tree'] ?? []))

<nav class="catmin-sidebar" aria-label="Navigation principale" data-nav-shell>
    <div class="catmin-sidebar__inner px-3 py-4">
        <div class="mb-3 px-2 d-flex align-items-center justify-content-between">
            <a href="{{ admin_route('index') }}" class="catmin-nav-brand">
                <img src="{{ asset('assets/img/logo_white.png') }}" alt="Catmin" class="catmin-nav-brand__logo">
                <span class="catmin-nav-brand__text">
                    <strong class="fs-5 d-block">CATMIN</strong>
                    <span class="small text-white-50 d-block">Administration</span>
                </span>
            </a>
            <button class="btn btn-sm btn-outline-light" type="button" data-nav-toggle aria-label="Basculer menu">
                <i class="bi bi-layout-sidebar"></i>
            </button>
        </div>

        <div class="catmin-nav-v2" data-nav-tree>
            @foreach($tree as $master)
                <section class="catmin-master {{ !empty($master['active']) ? 'is-active' : '' }}" data-master-id="{{ $master['id'] }}">
                    <button
                        class="catmin-master__btn"
                        type="button"
                        data-master-toggle
                        data-master-id="{{ $master['id'] }}"
                        aria-expanded="{{ !empty($master['opened']) ? 'true' : 'false' }}"
                    >
                        <span class="catmin-master__icon"><i class="{{ $master['icon'] ?? 'bi bi-grid-1x2' }}"></i></span>
                        <span class="catmin-master__label">{{ $master['label'] }}</span>
                        <i class="bi bi-chevron-down catmin-master__chevron"></i>
                    </button>

                    <div class="catmin-master__panel {{ !empty($master['opened']) ? 'is-open' : '' }}" data-master-panel="{{ $master['id'] }}">
                        @foreach($master['children'] as $sub)
                            <div class="catmin-sub {{ !empty($sub['active']) ? 'is-active' : '' }}">
                                <div class="catmin-sub__title">{{ $sub['label'] }}</div>
                                <div class="d-grid gap-1">
                                    @foreach($sub['children'] as $item)
                                        @include('admin.components.navigation.sidebar-item', ['item' => $item])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @include('admin.components.navigation.sidebar-flyout', ['master' => $master])
                </section>
            @endforeach
        </div>
    </div>
</nav>
