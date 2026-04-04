<nav class="navbar navbar-expand-lg cf-navbar sticky-top" role="navigation" aria-label="Navigation principale">
    <div class="container">

        {{-- Brand --}}
        <a class="navbar-brand" href="{{ route('frontend.home') }}">
            {{ $siteName ?? config('app.name', 'CATMIN') }}
        </a>

        {{-- Mobile toggler --}}
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#cf-main-nav"
                aria-controls="cf-main-nav"
                aria-expanded="false"
                aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Nav links --}}
        <div class="collapse navbar-collapse" id="cf-main-nav">
            @php($cfNavItems = $primaryMenu ?? menu_tree('primary'))
            @if($cfNavItems->isNotEmpty())
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @foreach($cfNavItems as $cfItem)
                        @if(!empty($cfItem['children']))
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle"
                                   href="{{ $cfItem['url'] }}"
                                   role="button"
                                   data-bs-toggle="dropdown"
                                   aria-expanded="false">
                                    {{ $cfItem['label'] }}
                                </a>
                                <ul class="dropdown-menu">
                                    @foreach($cfItem['children'] as $cfChild)
                                        <li>
                                            <a class="dropdown-item" href="{{ $cfChild['url'] }}">
                                                {{ $cfChild['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link @if(url()->current() === $cfItem['url']) active @endif"
                                   href="{{ $cfItem['url'] }}">
                                    {{ $cfItem['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif

            {{-- Optional secondary actions --}}
            <div class="d-flex gap-2">
                @if(config('catmin_frontend.contact_enabled'))
                    <a href="{{ route('frontend.contact') }}" class="btn btn-outline-primary btn-sm">Contact</a>
                @endif
            </div>
        </div>

    </div>
</nav>
