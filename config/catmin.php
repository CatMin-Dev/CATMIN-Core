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
        'dashboard_route' => '/admin',
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
    | Addons System Configuration
    |--------------------------------------------------------------------------
    |
    | Addons are external/optional extensions, separate from core modules.
    |
    */
    'addons' => [
        'path' => 'addons',
        'auto_load' => true,
        'auto_discover_routes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | RBAC Configuration
    |--------------------------------------------------------------------------
    |
    | Progressive RBAC: legacy admin keeps '*' permission, while routes/menu
    | can progressively declare fine-grained permissions.
    |
    */
    'rbac' => [
        'enabled' => true,
        'convention' => 'module.<slug>.<action>',
        'actions' => ['menu', 'list', 'create', 'edit', 'delete', 'config'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal REST API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'prefix' => 'api/internal',
        'internal_token' => env('CATMIN_API_INTERNAL_TOKEN', ''),
        'external' => [
            'enabled' => env('CATMIN_EXTERNAL_API_ENABLED', true),
            'prefix' => env('CATMIN_EXTERNAL_API_PREFIX', 'api/v2'),
            'rate_limit_per_minute' => (int) env('CATMIN_EXTERNAL_API_RATE_LIMIT', 120),
            'default_scope' => 'external.read',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication (212)
    |--------------------------------------------------------------------------
    | CATMIN_2FA_ENABLED=true  active la 2FA sur la connexion admin.
    | CATMIN_2FA_SECRET        secret TOTP 32 chars (généré via setup page).
    */
    'two_factor' => [
        'enabled' => env('CATMIN_2FA_ENABLED', false),
        'secret'  => env('CATMIN_2FA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        // Token dans l'URL pour les webhooks entrants (/webhooks/incoming/{token})
        'incoming_token'  => env('CATMIN_WEBHOOK_INCOMING_TOKEN', ''),
        // Secret HMAC optionnel — si défini, la signature X-Hub-Signature-256 sera vérifiée (218)
        'incoming_secret' => env('CATMIN_WEBHOOK_INCOMING_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads Security
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_file_kb' => 20480,
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'txt', 'csv', 'json',
            'mp4', 'webm', 'mp3',
            'zip',
        ],
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
                'title' => 'Administration',
                'items' => [
                    [
                        'label' => 'Tableau de bord',
                        'icon' => 'bi bi-house',
                        'route' => 'index',
                    ],
                    [
                        'label' => 'Utilisateurs',
                        'icon' => 'bi bi-people',
                        'route' => 'users.index',
                        'active_when' => ['users.*'],
                        'permission' => 'module.users.menu',
                    ],
                    [
                        'label' => 'Roles',
                        'icon' => 'bi bi-shield-check',
                        'route' => 'roles.manage',
                        'active_when' => ['roles.*'],
                        'permission' => 'module.users.config',
                    ],
                    [
                        'label' => 'Parametres',
                        'icon' => 'bi bi-sliders',
                        'route' => 'settings.index',
                        'active_when' => ['settings.*'],
                        'permission' => 'module.settings.menu',
                    ],
                    [
                        'label' => 'Modules',
                        'icon' => 'bi bi-puzzle',
                        'route' => 'modules.index',
                        'active_when' => ['modules.*'],
                        'permission' => 'module.core.config',
                    ],
                    [
                        'label' => 'Logs',
                        'icon' => 'bi bi-journal-code',
                        'route' => 'logger.index',
                        'active_when' => ['logger.*'],
                        'module' => 'logger',
                        'permission' => 'module.logger.menu',
                    ],
                    [
                        'label' => 'Cache',
                        'icon' => 'bi bi-lightning-charge',
                        'route' => 'cache.index',
                        'active_when' => ['cache.*'],
                        'module' => 'cache',
                        'permission' => 'module.cache.menu',
                    ],
                    [
                        'label' => 'Planificateur',
                        'icon' => 'bi bi-clock-history',
                        'route' => 'cron.index',
                        'active_when' => ['cron.*'],
                        'module' => 'cron',
                        'permission' => 'module.cron.menu',
                    ],
                    [
                        'label' => 'Queue',
                        'icon' => 'bi bi-stack',
                        'route' => 'queue.index',
                        'active_when' => ['queue.*'],
                        'module' => 'queue',
                        'permission' => 'module.queue.menu',
                    ],
                ],
            ],
            [
                'title' => 'Intégrations',
                'items' => [
                    [
                        'label' => 'Webhooks',
                        'icon' => 'bi bi-send',
                        'route' => 'webhooks.index',
                        'active_when' => ['webhooks.*'],
                        'module' => 'webhooks',
                        'permission' => 'module.webhooks.menu',
                    ],
                ],
            ],
            [
                'title' => 'CMS',
                'items' => [
                    [
                        'label' => 'Pages',
                        'icon' => 'bi bi-file-earmark-text',
                        'route' => 'content.show',
                        'parameters' => ['module' => 'pages'],
                        'match_module' => 'pages',
                        'active_when' => ['content.show', 'pages.*'],
                        'permission' => 'module.pages.menu',
                    ],
                    [
                        'label' => 'Articles',
                        'icon' => 'bi bi-journal-text',
                        'route' => 'content.show',
                        'parameters' => ['module' => 'articles'],
                        'match_module' => 'articles',
                        'active_when' => ['content.show', 'articles.*'],
                        'permission' => 'module.articles.menu',
                    ],
                    [
                        'label' => 'Media',
                        'icon' => 'bi bi-images',
                        'route' => 'content.show',
                        'parameters' => ['module' => 'media'],
                        'match_module' => 'media',
                        'active_when' => ['content.show', 'media.*'],
                        'permission' => 'module.media.menu',
                    ],
                    [
                        'label' => 'Menus',
                        'icon' => 'bi bi-list',
                        'route' => 'content.show',
                        'parameters' => ['module' => 'menus'],
                        'match_module' => 'menus',
                        'active_when' => ['content.show', 'menus.*'],
                        'module' => 'menus',
                        'permission' => 'module.menus.menu',
                    ],
                    [
                        'label' => 'Blocks',
                        'icon' => 'bi bi-grid-3x3-gap',
                        'route' => 'content.show',
                        'parameters' => ['module' => 'blocks'],
                        'match_module' => 'blocks',
                        'active_when' => ['content.show', 'blocks.*'],
                        'module' => 'blocks',
                        'permission' => 'module.blocks.menu',
                    ],
                ],
            ],
            [
                'title' => 'Commerce',
                'items' => [
                    [
                        'label' => 'Shop',
                        'icon' => 'bi bi-bag',
                        'route' => 'shop.manage',
                        'active_when' => ['shop.*'],
                        'module' => 'shop',
                        'permission' => 'module.shop.menu',
                    ],
                ],
            ],
            [
                'title' => 'Modules actifs',
                'source' => 'enabled_modules',
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
    | Frontend Foundation
    |--------------------------------------------------------------------------
    |
    | Lightweight public frontend settings. The legacy PHP frontend stays in
    | place, while this configuration prepares a Laravel-native public layer.
    |
    */
    'frontend' => [
        'enabled' => true,
        'path' => 'site',
        'theme' => 'catmin-public',
        'legacy_path' => 'frontend',
        'data_sources' => [
            'settings' => true,
            'pages' => true,
            'contents' => true,
            'menus' => true,
            'modules' => true,
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
