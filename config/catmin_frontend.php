<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Bootstrap CDN
    |--------------------------------------------------------------------------
    |
    | The public frontend intentionally loads Bootstrap from a CDN and does
    | NOT share the admin bundle. This keeps admin and public assets isolated.
    |
    */

    'bootstrap_css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'bootstrap_js'  => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',

    /*
    |--------------------------------------------------------------------------
    | Articles
    |--------------------------------------------------------------------------
    */

    'articles_per_page' => 12,
    'articles_prefix'   => 'articles',    // URL prefix for article listing/detail

    /*
    |--------------------------------------------------------------------------
    | Contact form
    |--------------------------------------------------------------------------
    */

    'contact_enabled'   => true,
    'contact_to_email'  => env('CATMIN_CONTACT_EMAIL', null),   // null = no mail sent
    'contact_max_chars' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Public map
    |--------------------------------------------------------------------------
    |
    | The map section is shown only when the catmin-map addon is enabled.
    | Leaflet is loaded from CDN.
    |
    */

    'map_enabled'   => true,
    'leaflet_css'   => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'leaflet_js'    => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'map_default_lat'  => 48.8566,
    'map_default_lng'  => 2.3522,
    'map_default_zoom' => 6,

    /*
    |--------------------------------------------------------------------------
    | Home page slug
    |--------------------------------------------------------------------------
    */

    'home_page_slug' => 'home',

];
