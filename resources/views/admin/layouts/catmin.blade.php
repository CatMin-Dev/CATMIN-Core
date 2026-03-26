<!doctype html>
<html lang="fr">
<head>
    @include('admin.partials.head')
</head>
<body class="nav-md page-{{ $currentPage ?? 'dashboard' }}">
<div class="container body">
    <div class="main_container">
        @include('admin.partials.aside')
        @include('admin.partials.topnav')

        <main class="right_col" role="main" aria-label="Main content">
            @yield('content')
        </main>

        @include('admin.partials.footer')
    </div>
</div>
</body>
</html>
