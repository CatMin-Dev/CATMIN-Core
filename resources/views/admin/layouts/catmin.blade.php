<!doctype html>
<html lang="fr">
<head>
    @include('admin.partials.head')
</head>
<body class="nav-md page-{{ $currentPage ?? 'dashboard' }}@if($currentPage === 'fixed_footer') footer_fixed@endif@if($currentPage === 'dashboard') page-index@endif">
<div class="container-fluid body">
    <div class="main_container">
        @include('admin.partials.aside')
        @include('admin.partials.topnav')

        <main class="right_col" role="main" aria-label="Main content">
            <!-- Dynamic Content Area -->
            <div class="clearfix"></div>
            @yield('content')
            <div class="clearfix"></div>
        </main>

        @include('admin.partials.footer')
    </div>
</div>
</body>
</html>
