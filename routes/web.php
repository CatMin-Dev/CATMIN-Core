<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/frontend/index.php');
});

Route::get('/dashboard', function () {
    return redirect('/dashboard/index.php?page=dashboard');
});

Route::get('/dashboard/{page}', function (string $page) {
    $allowedPages = [
        'dashboard',
        'calendar',
        'chartjs',
        'contacts',
        'e_commerce',
        'echarts',
        'fixed_footer',
        'fixed_sidebar',
        'form',
        'form_advanced',
        'form_buttons',
        'form_upload',
        'form_validation',
        'form_wizards',
        'general_elements',
        'icons',
        'inbox',
        'invoice',
        'level2',
        'map',
        'media_gallery',
        'other_charts',
        'plain_page',
        'pricing_tables',
        'profile',
        'project_detail',
        'projects',
        'tables',
        'tables_dynamic',
        'typography',
        'widgets',
    ];

    $sanitizedPage = strtolower($page);

    if (!preg_match('/^[a-z0-9_\-]+$/', $sanitizedPage) || !in_array($sanitizedPage, $allowedPages, true)) {
        $sanitizedPage = 'dashboard';
    }

    return redirect('/dashboard/index.php?page=' . $sanitizedPage);
});

Route::view('/admin/bridge', 'admin.bridge');
