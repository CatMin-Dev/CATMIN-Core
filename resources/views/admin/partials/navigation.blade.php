@php($navigationSections = \App\Services\AdminNavigationService::sections($currentPage ?? null))

<nav class="catmin-sidebar" aria-label="Navigation principale">
    <div class="catmin-sidebar__inner px-3 py-4">
        <div class="mb-4 px-2">
            <a href="{{ admin_route('index') }}" class="catmin-nav-brand">
                <img src="{{ asset('assets/img/logo_white.png') }}" alt="Catmin" class="catmin-nav-brand__logo">
                <span class="catmin-nav-brand__text">
                    <strong class="fs-5 d-block">CATMIN</strong>
                    <span class="small text-white-50 d-block">Administration</span>
                </span>
            </a>
        </div>

        <div class="catmin-nav d-grid gap-4">
            @foreach($navigationSections as $section)
                @continue(strtolower($section['title'] ?? '') === 'modules actifs')
                <section class="catmin-nav-section">
                    <p class="catmin-nav-section-title mb-2 px-2">{{ $section['title'] }}</p>
                    <ul class="catmin-nav-list d-flex flex-column gap-1 p-0 m-0">
                        @foreach($section['items'] as $item)
                            <li class="catmin-nav-item">
                                <a href="{{ $item['url'] }}" class="catmin-nav-link {{ !empty($item['active']) ? 'active' : '' }}" @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif>
                                    <i class="{{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                    @if(!empty($item['badge']))
                                        <span class="badge text-bg-light ms-auto">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</nav>
