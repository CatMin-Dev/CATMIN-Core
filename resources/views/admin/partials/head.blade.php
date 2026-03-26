<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="CATMIN Administration - Gestion progressive">

<!-- Security Headers -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

<!-- Favicons -->
<link rel="icon" href="{{ asset('dashboard/assets/img/icon.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('dashboard/assets/img/icon.png') }}" sizes="180x180">

<!-- Theme colors -->
<meta name="theme-color" content="rgb(42, 63, 84)">
<meta name="msapplication-TileColor" content="rgb(42, 63, 84)">

<title>CATMIN {{ $currentPage ? '- ' . ucfirst(str_replace('_', ' ', $currentPage)) : 'Admin' }}</title>

<!-- Base URL for relative assets -->
<base href="{{ asset('dashboard/') }}">

<!-- ========== CSS Stylesheets ========== -->

<!-- Bootstrap Core CSS -->
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap-5.3.8/css/bootstrap.css') }}">

<!-- Icon Libraries -->
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/@fortawesome/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/bootstrap-icons/font/bootstrap-icons.min.css') }}">

<!-- Date/Time Picker CSS -->
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/@eonasdan/tempus-dominus/dist/css/tempus-dominus.min.css') }}">

<!-- Map CSS (Leaflet) -->
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/leaflet/dist/leaflet.css') }}">

<!-- Main Custom Theme CSS (Must be last for overrides) -->
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/themes.css') }}">
<link rel="stylesheet" href="{{ asset('dashboard/assets/css/catmin.css') }}">

<!-- ========== JavaScript (defer for performance) ========== -->

<!-- Main application bundle -->
<script type="module" defer src="{{ asset('dashboard/assets/js/main-minimal.js') }}"></script>
