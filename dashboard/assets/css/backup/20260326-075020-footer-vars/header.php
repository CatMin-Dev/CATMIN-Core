<?php
$pageForBody = isset($currentPage) && preg_match('/^[a-z0-9_\-]+$/', $currentPage)
  ? $currentPage
  : 'dashboard';
$bodyLayoutClasses = [];

if ($pageForBody === 'fixed_footer') {
  $bodyLayoutClasses[] = 'footer_fixed';
}

if ($pageForBody === 'dashboard') {
  $bodyLayoutClasses[] = 'page-index';
}

$bodyClassAttr = trim('nav-md page-' . $pageForBody . ' ' . implode(' ', $bodyLayoutClasses));
?>
<!DOCTYPE html>
<html lang="en" data-theme="catmin-light">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="/rework/">
    
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: https: blob:; font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self' ws: wss: http://localhost:* https://api.example.com https://*.googleapis.com; frame-src 'self' https://www.youtube.com https://player.vimeo.com; media-src 'self' https: blob:; object-src 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests;">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="camera=(), microphone=(), geolocation=()">
    <link rel="icon" href="assets/images/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="assets/images/favicon-32x32.svg" type="image/svg+xml" sizes="32x32">
    <link rel="icon" href="assets/images/favicon-16x16.svg" type="image/svg+xml" sizes="16x16">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.svg" sizes="180x180">
    
    <!-- Android/Chrome -->
    <link rel="manifest" href="site.webmanifest">
    
    <!-- Theme colors -->
    <meta name="theme-color" content="rgb(42, 63, 84)">
    <meta name="msapplication-TileColor" content="rgb(42, 63, 84)">

    <title>Catmin Admin</title>
    <link rel="stylesheet" href="assets/css/bootstrap-5.3.8/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/catmin.css">

    <script src="assets/js/vendor/browser-globals-shim.js"></script>
    <script type="importmap">
{
  "imports": {
    "bootstrap": "/rework/vendor/node_modules/bootstrap/dist/js/bootstrap.esm.js",
    "choices.js": "/rework/vendor/node_modules/choices.js/public/assets/scripts/choices.mjs",
    "nouislider": "/rework/vendor/node_modules/nouislider/dist/nouislider.mjs",
    "@eonasdan/tempus-dominus": "/rework/assets/js/vendor/tempus-dominus-esm-adapter.js",
    "chart.js": "/rework/vendor/node_modules/chart.js/dist/chart.js",
    "echarts": "/rework/vendor/node_modules/echarts/index.js",
    "skycons": "/rework/assets/js/vendor/skycons-adapter.js",
    "leaflet": "/rework/vendor/node_modules/leaflet/dist/leaflet-src.esm.js",
    "inputmask": "/rework/vendor/node_modules/inputmask/dist/inputmask.es6.js",
    "@simonwep/pickr": "/rework/assets/js/vendor/pickr-adapter.js",
    "cropperjs": "/rework/vendor/node_modules/cropperjs/dist/cropper.esm.js",
    "datatables.net": "/rework/vendor/node_modules/datatables.net/js/dataTables.mjs",
    "datatables.net-bs5": "/rework/vendor/node_modules/datatables.net-bs5/js/dataTables.bootstrap5.mjs",
    "datatables.net-responsive": "/rework/vendor/node_modules/datatables.net-responsive/js/dataTables.responsive.mjs",
    "datatables.net-responsive-bs5": "/rework/vendor/node_modules/datatables.net-responsive-bs5/js/responsive.bootstrap5.mjs",
    "datatables.net-buttons": "/rework/vendor/node_modules/datatables.net-buttons/js/dataTables.buttons.mjs",
    "datatables.net-buttons-bs5": "/rework/vendor/node_modules/datatables.net-buttons-bs5/js/buttons.bootstrap5.mjs",
    "datatables.net-buttons/": "/rework/vendor/node_modules/datatables.net-buttons/",
    "datatables.net-fixedheader": "/rework/vendor/node_modules/datatables.net-fixedheader/js/dataTables.fixedHeader.mjs",
    "datatables.net-keytable": "/rework/vendor/node_modules/datatables.net-keytable/js/dataTables.keyTable.mjs",
    "jszip": "/rework/assets/js/vendor/jszip-esm-adapter.js",
    "dompurify": "/rework/vendor/node_modules/dompurify/dist/purify.es.mjs",
    "@fullcalendar/core": "/rework/vendor/node_modules/@fullcalendar/core/index.js",
    "@fullcalendar/daygrid": "/rework/vendor/node_modules/@fullcalendar/daygrid/index.js",
    "@fullcalendar/interaction": "/rework/vendor/node_modules/@fullcalendar/interaction/index.js",
    "@fullcalendar/timegrid": "/rework/vendor/node_modules/@fullcalendar/timegrid/index.js",
    "dayjs": "/rework/vendor/node_modules/dayjs/esm/index.js",
    "quill": "/rework/vendor/node_modules/quill/quill.js",
    "@uppy/core": "/rework/vendor/node_modules/@uppy/core/lib/index.js",
    "@uppy/dashboard": "/rework/vendor/node_modules/@uppy/dashboard/lib/index.js",
    "@uppy/xhr-upload": "/rework/vendor/node_modules/@uppy/xhr-upload/lib/index.js",
    "@popperjs/core": "/rework/vendor/node_modules/@popperjs/core/lib/index.js",
    "@kurkle/color": "/rework/vendor/node_modules/@kurkle/color/dist/color.esm.js",
    "zrender": "/rework/vendor/node_modules/zrender/index.js",
    "tslib": "/rework/vendor/node_modules/tslib/tslib.es6.js",
    "tslib/": "/rework/vendor/node_modules/tslib/",
    "jquery": "/rework/assets/js/vendor/jquery-esm-adapter.js",
    "@fullcalendar/": "/rework/vendor/node_modules/@fullcalendar/",
    "@uppy/": "/rework/vendor/node_modules/@uppy/",
    "dayjs/": "/rework/vendor/node_modules/dayjs/",
    "datatables.net/": "/rework/vendor/node_modules/datatables.net/",
    "@eonasdan/tempus-dominus/": "/rework/vendor/node_modules/@eonasdan/tempus-dominus/",
    "bootstrap/": "/rework/vendor/node_modules/bootstrap/",
    "echarts/": "/rework/vendor/node_modules/echarts/",
    "zrender/": "/rework/vendor/node_modules/zrender/",
    "quill/": "/rework/vendor/node_modules/quill/",
    "leaflet/": "/rework/vendor/node_modules/leaflet/",
    "choices.js/": "/rework/vendor/node_modules/choices.js/",
    "nouislider/": "/rework/vendor/node_modules/nouislider/",
    "chart.js/": "/rework/vendor/node_modules/chart.js/"
  }
}
    </script>
  <script type="module" src="/rework/assets/js/main-minimal.js"></script>
  </head>

  <body class="<?php echo htmlspecialchars($bodyClassAttr, ENT_QUOTES, 'UTF-8'); ?>" data-page="<?php echo htmlspecialchars($pageForBody, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="container body">
      <div class="main_container">