<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CATMIN Administration Configuration
    |--------------------------------------------------------------------------
    |
    | Central configuration for CATMIN administration panel, routing, and access.
    | All values are environment-aware and configurable for future sub-domain
    | or path-based routing strategies.
    |
    */

    'admin' => [
        /*
        | Authentication (Session-based)
        */
        'username' => env('CATMIN_ADMIN_USERNAME', 'admin'),
        'password' => env('CATMIN_ADMIN_PASSWORD', 'admin12345'),

        /*
        | Routing Configuration
        */
        'path' => env('CATMIN_ADMIN_PATH', 'admin'),
        'subdomain' => env('CATMIN_ADMIN_SUBDOMAIN', null), // null = no subdomain, 'admin' = admin.catmin.local
        'prefix' => env('CATMIN_ADMIN_PREFIX', '/admin'),   // Full prefix override if needed

        /*
        | Middleware for Admin Routes
        */
        'middleware' => ['web', 'catmin.admin'],

        /*
        | Naming Convention for Routes
        | Used to generate route names: admin.login, admin.dashboard, etc.
        */
        'route_namespace' => 'admin',

        /*
        | Session Configuration
        */
        'session_key' => 'catmin_admin_authenticated',
        'session_lifetime' => 120, // minutes

        /*
        | Entry Points (for future)
        */
        'login_route' => '/admin/login',
        'dashboard_route' => '/admin/access',
        'logout_route' => '/admin/logout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Legacy Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for interacting with the legacy PHP dashboard
    |
    */
    'dashboard' => [
        'path' => 'dashboard',
        'content_dir' => 'dashboard/content',
        'components_dir' => 'dashboard/components',
        'assets_dir' => 'dashboard/assets',

        /*
        | Whitelist of accessible content pages
        |
        | This list maps to actual HTML files in dashboard/content/
        | Future: This will be sourced from database or dynamic configuration
        */
        'pages_whitelist' => [
            'dashboard', 'calendar', 'chartjs', 'forms_basic', 'forms_advanced',
            'forms_elements', 'forms_layouts', 'forms_validation', 'forms_wizard',
            'table_bootstrap', 'table_datatable', 'chart_bars', 'chart_lines',
            'chart_mixed', 'ecommerce_cart', 'ecommerce_list', 'ecommerce_summary',
            'profil_page', 'projects_grid', 'projects_list', 'contacts_page',
            'inbox_page', 'general_elements', 'icons', 'media_gallery',
            'typography_page', 'widgets', 'map_embed', 'plain_page',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module System Configuration
    |--------------------------------------------------------------------------
    |
    | Controls module loading, activation, and dependency resolution
    |
    */
    'modules' => [
        'path' => 'modules',
        'auto_load' => true,
        'auto_discover_routes' => true,
        'auto_discover_migrations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Sidebar sections are defined here so the UI is not frozen inside Blade.
    | Items can target legacy preview pages, admin routes, or external URLs.
    | Module-backed sections are filtered automatically based on active modules.
    |
    */
    'navigation' => [
        'sections' => [
            [
                'title' => 'Apercu',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'bi bi-house',
                        'legacy_page' => 'dashboard',
                    ],
                    [
                        'label' => 'Composants',
                        'icon' => 'bi bi-grid',
                        'legacy_page' => 'widgets',
                    ],
                ],
            ],
            [
                'title' => 'Visuels',
                'items' => [
                    [
                        'label' => 'Graphiques',
                        'icon' => 'bi bi-bar-chart',
                        'legacy_page' => 'chartjs',
                    ],
                    [
                        'label' => 'Tableaux',
                        'icon' => 'bi bi-table',
                        'legacy_page' => 'table_bootstrap',
                    ],
                    [
                        'label' => 'Galerie',
                        'icon' => 'bi bi-images',
                        'legacy_page' => 'media_gallery',
                    ],
                ],
            ],
            [
                'title' => 'Formulaires',
                'items' => [
                    [
                        'label' => 'Formulaires de base',
                        'icon' => 'bi bi-input-cursor-text',
                        'legacy_page' => 'forms_basic',
                    ],
                ],
            ],
            [
                'title' => 'Modules actifs',
                'source' => 'enabled_modules',
            ],
            [
                'title' => 'Legacy',
                'items' => [
                    [
                        'label' => 'Dashboard PHP',
                        'icon' => 'bi bi-box-arrow-up-right',
                        'path' => '/dashboard/index.php',
                        'target' => '_blank',
                        'feature' => 'legacy_preview_enabled',
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Settings System
    |--------------------------------------------------------------------------
    |
    | Defaults used by the settings service. Database values override these
    | entries, but keeping sane defaults here avoids hard failures during the
    | initial installation phase.
    |
    */
    'settings' => [
        'cache_key' => 'catmin.settings',
        'defaults' => [
            'site.name' => 'CATMIN',
            'site.url' => env('APP_URL', 'http://catmin.local'),
            'admin.theme' => 'catmin-light',
            'admin.path' => env('CATMIN_ADMIN_PATH', 'admin'),
            'site.frontend_enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features & Flags
    |--------------------------------------------------------------------------
    |
    | Feature toggles for gradual rollout of functionality
    |
    */
    'features' => [
        'legacy_preview_enabled' => true,
        'admin_authentication_enabled' => true,
        'module_system_enabled' => false, // Enable when modules are ready
        'api_enabled' => false,
    ],
];
