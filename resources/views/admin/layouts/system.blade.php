<!DOCTYPE html>
<html lang="en" data-theme="{{ catmin_theme() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('dashboard/assets/img/icon.png') }}" type="image/png">
    <title>@yield('title', 'CATMIN')</title>
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap-5.3.8/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/themes.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/assets/css/catmin.css') }}">
</head>
<body class="@yield('body_class', 'page-login login-bg')">
    @yield('content')
    @stack('scripts')
</body>
</html>
