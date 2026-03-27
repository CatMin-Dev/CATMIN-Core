<!doctype html>
<html lang="fr">
<head>
    @include('admin.partials.head')
    <title>@yield('title', 'CATMIN')</title>
</head>
<body class="bg-body-tertiary">
    @yield('content')
    @stack('scripts')
</body>
</html>
