<aside class="col-md-3 left_col" aria-label="Sidebar navigation">
    @php($navigationSections = \App\Services\AdminNavigationService::sections($currentPage))

    <div class="left_col scroll-view">
        <!-- Brand Header -->
        <div class="navbar nav_title border-0">
            <a href="{{ admin_route('preview', ['page' => 'dashboard']) }}" class="site_title">
                <img src="assets/img/logo_white.png" alt="Catmin" class="logo-full logo-main" loading="lazy">
                <div class="site_brand_text">
                    <span class="site_brand_name">CATMIN</span>
                    <small class="site_brand_subtitle">Admin Progressif</small>
                </div>
                <small class="site_brand_compact">CAT</small>
            </a>
        </div>

        <div class="clearfix"></div>

        <!-- Profile Section -->
        <div class="profile clearfix">
            <div class="profile_pic">
                <img src="assets/img/img.jpg" alt="User preview" class="img-circle profile_img" loading="lazy">
            </div>
            <div class="profile_info">
                <span>Admin</span>
                <h4>{{ $currentPage ? ucfirst(str_replace('_', ' ', $currentPage)) : 'Dashboard' }}</h4>
            </div>
        </div>

        <br />

        <!-- Navigation Menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            @foreach($navigationSections as $section)
                <div class="menu_section">
                    <h3 class="heading"><span>{{ $section['title'] }}</span></h3>
                    <ul class="nav side-menu">
                        @foreach($section['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                   @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
                                   class="@if(!empty($item['active'])) active @endif">
                                    <i class="{{ $item['icon'] }}"></i>
                                    {{ $item['label'] }}
                                    @if(!empty($item['badge']))
                                        <span class="badge bg-secondary float-end mt-1">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</aside>
