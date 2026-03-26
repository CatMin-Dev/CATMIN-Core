<aside class="col-md-3 left_col" aria-label="Sidebar navigation">
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
            <div class="menu_section">
                <h3 class="heading"><span>Aperçu</span></h3>
                <ul class="nav side-menu">
                    <li><a href="{{ admin_route('preview', ['page' => 'dashboard']) }}" class="@if($currentPage === 'dashboard') active @endif"><i class="bi bi-house"></i> Dashboard</a></li>
                    <li><a href="{{ admin_route('preview', ['page' => 'widgets']) }}" class="@if($currentPage === 'widgets') active @endif"><i class="bi bi-grid"></i> Composants</a></li>
                </ul>
            </div>
            <div class="menu_section">
                <h3 class="heading"><span>Visuels</span></h3>
                <ul class="nav side-menu">
                    <li><a href="{{ admin_route('preview', ['page' => 'chartjs']) }}" class="@if($currentPage === 'chartjs') active @endif"><i class="bi bi-bar-chart"></i> Graphiques</a></li>
                    <li><a href="{{ admin_route('preview', ['page' => 'table_bootstrap']) }}" class="@if($currentPage === 'table_bootstrap') active @endif"><i class="bi bi-table"></i> Tableaux</a></li>
                    <li><a href="{{ admin_route('preview', ['page' => 'media_gallery']) }}" class="@if($currentPage === 'media_gallery') active @endif"><i class="bi bi-images"></i> Galerie</a></li>
                </ul>
            </div>
            <div class="menu_section">
                <h3 class="heading"><span>Formulaires</span></h3>
                <ul class="nav side-menu">
                    <li><a href="{{ admin_route('preview', ['page' => 'forms_basic']) }}" class="@if($currentPage === 'forms_basic') active @endif"><i class="bi bi-input-cursor-text"></i> Formulaires de base</a></li>
                </ul>
            </div>
            <div class="menu_section">
                <h3 class="heading"><span>Legacy</span></h3>
                <ul class="nav side-menu">
                    <li><a href="/dashboard/index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Dashboard PHP</a></li>
                </ul>
            </div>
        </div>
    </div>
</aside>
