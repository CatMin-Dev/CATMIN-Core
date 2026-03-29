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
        'session_idle_timeout_minutes' => (int) env('CATMIN_ADMIN_SESSION_IDLE_TIMEOUT', 120),

        /*
        | Entry Points (for future)
        */
        'login_route' => '/admin/login',
        'dashboard_route' => '/admin',
        'logout_route' => '/admin/logout',
        'password_reset_expire_minutes' => (int) env('CATMIN_ADMIN_PASSWORD_RESET_EXPIRE', 60),
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

    'health' => [
        'failed_jobs_threshold' => (int) env('CATMIN_HEALTH_FAILED_JOBS_THRESHOLD', 50),
    ],

    'performance' => [
        'slow_request_ms' => (int) env('CATMIN_SLOW_REQUEST_MS', 800),
        'slow_query_ms' => (int) env('CATMIN_SLOW_QUERY_MS', 250),
        'slow_job_ms' => (int) env('CATMIN_SLOW_JOB_MS', 1500),
        'dashboard_cache_ttl_seconds' => (int) env('CATMIN_DASHBOARD_CACHE_TTL', 60),
        'monitoring_widget_cache_ttl_seconds' => (int) env('CATMIN_MONITORING_WIDGET_CACHE_TTL', 30),
        'public_api_default_per_page' => (int) env('CATMIN_PUBLIC_API_PER_PAGE', 25),
        'public_api_max_per_page' => (int) env('CATMIN_PUBLIC_API_MAX_PER_PAGE', 100),
        'budgets' => [
            [
                'key' => 'admin.login',
                'label' => 'Login admin',
                'category' => 'auth',
                'route' => 'admin.login',
                'target_response_ms' => 250,
                'max_response_ms' => 450,
                'max_queries' => 4,
                'max_slow_queries' => 0,
                'notes' => 'Doit rester tres reactif.',
            ],
            [
                'key' => 'admin.dashboard',
                'label' => 'Dashboard home',
                'category' => 'dashboard',
                'route' => 'admin.index',
                'target_response_ms' => 350,
                'max_response_ms' => 700,
                'max_queries' => 18,
                'max_slow_queries' => 1,
                'notes' => 'S appuie sur cache court pour les KPI.',
            ],
            [
                'key' => 'admin.monitoring',
                'label' => 'Monitoring center',
                'category' => 'ops',
                'route' => 'admin.monitoring.index',
                'target_response_ms' => 350,
                'max_response_ms' => 750,
                'max_queries' => 16,
                'max_slow_queries' => 1,
                'notes' => 'Vue transverse, doit rester lisible et actionnable.',
            ],
            [
                'key' => 'admin.performance',
                'label' => 'Performance center',
                'category' => 'ops',
                'route' => 'admin.performance.index',
                'target_response_ms' => 400,
                'max_response_ms' => 900,
                'max_queries' => 12,
                'max_slow_queries' => 1,
                'notes' => 'Reporting technique, lecture admin.',
            ],
            [
                'key' => 'admin.logs',
                'label' => 'Logs listing',
                'category' => 'listing',
                'route' => 'admin.logger.index',
                'target_response_ms' => 450,
                'max_response_ms' => 900,
                'max_queries' => 10,
                'max_slow_queries' => 1,
                'notes' => 'Pagination et filtres doivent suffire.',
            ],
            [
                'key' => 'admin.queue',
                'label' => 'Queue listing',
                'category' => 'listing',
                'route' => 'admin.queue.index',
                'target_response_ms' => 450,
                'max_response_ms' => 900,
                'max_queries' => 10,
                'max_slow_queries' => 1,
                'notes' => 'Eviter les scans lourds repetes.',
            ],
            [
                'key' => 'admin.settings',
                'label' => 'Settings panels',
                'category' => 'settings',
                'route' => 'admin.settings.manage',
                'target_response_ms' => 350,
                'max_response_ms' => 700,
                'max_queries' => 8,
                'max_slow_queries' => 0,
                'notes' => 'Lecture frequente, peu de volume attendu.',
            ],
            [
                'key' => 'admin.pages',
                'label' => 'Pages listing',
                'category' => 'listing',
                'route' => 'admin.pages.manage',
                'target_response_ms' => 350,
                'max_response_ms' => 700,
                'max_queries' => 8,
                'max_slow_queries' => 0,
                'notes' => 'Selection allegee et pagination stricte.',
            ],
            [
                'key' => 'admin.articles',
                'label' => 'Articles listing',
                'category' => 'listing',
                'route' => 'admin.articles.manage',
                'target_response_ms' => 350,
                'max_response_ms' => 700,
                'max_queries' => 8,
                'max_slow_queries' => 0,
                'notes' => 'Eviter lecture de colonnes lourdes en liste.',
            ],
            [
                'key' => 'admin.media',
                'label' => 'Media listing',
                'category' => 'listing',
                'route' => 'admin.media.manage',
                'target_response_ms' => 450,
                'max_response_ms' => 900,
                'max_queries' => 8,
                'max_slow_queries' => 1,
                'notes' => 'Filtrage indexable et colonnes utiles seulement.',
            ],
            [
                'key' => 'admin.shop.orders',
                'label' => 'Shop orders',
                'category' => 'listing',
                'route' => 'admin.shop.orders.index',
                'target_response_ms' => 450,
                'max_response_ms' => 900,
                'max_queries' => 12,
                'max_slow_queries' => 1,
                'notes' => 'Surveiller eager loading customer/items.',
            ],
            [
                'key' => 'api.v2.pages',
                'label' => 'API v2 pages published',
                'category' => 'api',
                'path' => 'api/v2/pages/published',
                'target_response_ms' => 250,
                'max_response_ms' => 500,
                'max_queries' => 6,
                'max_slow_queries' => 0,
                'notes' => 'Pagination obligatoire.',
            ],
            [
                'key' => 'api.v2.articles',
                'label' => 'API v2 articles published',
                'category' => 'api',
                'path' => 'api/v2/articles/published',
                'target_response_ms' => 250,
                'max_response_ms' => 500,
                'max_queries' => 6,
                'max_slow_queries' => 0,
                'notes' => 'Pagination obligatoire.',
            ],
            [
                'key' => 'api.v2.shop.products',
                'label' => 'API v2 shop products',
                'category' => 'api',
                'path' => 'api/v2/shop/products',
                'target_response_ms' => 250,
                'max_response_ms' => 500,
                'max_queries' => 6,
                'max_slow_queries' => 0,
                'notes' => 'Limiter la taille de reponse.',
            ],
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
        // Optional incoming webhook row id used for anti-replay/idempotence state tracking.
        'incoming_webhook_id' => env('CATMIN_WEBHOOK_INCOMING_ID', null),
    ],

    'security' => [
        'headers' => [
            'enabled' => env('CATMIN_SECURITY_HEADERS_ENABLED', true),
            'csp' => env(
                'CATMIN_SECURITY_CSP',
                "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; object-src 'none'; img-src 'self' data: blob: https:; media-src 'self' data: blob: https:; font-src 'self' data: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; connect-src 'self' https: wss: ws:"
            ),
            'frame_options' => env('CATMIN_SECURITY_FRAME_OPTIONS', 'DENY'),
            'referrer_policy' => env('CATMIN_SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
            'permissions_policy' => env('CATMIN_SECURITY_PERMISSIONS_POLICY', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()'),
            'hsts' => [
                'enabled' => env('CATMIN_SECURITY_HSTS_ENABLED', true),
                'max_age' => (int) env('CATMIN_SECURITY_HSTS_MAX_AGE', 31536000),
                'include_subdomains' => env('CATMIN_SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
                'preload' => env('CATMIN_SECURITY_HSTS_PRELOAD', false),
            ],
            'sensitive_paths' => [
                env('CATMIN_ADMIN_PATH', 'admin') . '/login',
                env('CATMIN_ADMIN_PATH', 'admin') . '/forgot-password',
                env('CATMIN_ADMIN_PATH', 'admin') . '/reset-password',
                env('CATMIN_ADMIN_PATH', 'admin') . '/2fa',
            ],
        ],
        'guardrails' => [
            'enabled' => env('CATMIN_SECURITY_GUARDRAILS_ENABLED', true),
            'min_secret_length' => (int) env('CATMIN_SECURITY_MIN_SECRET_LENGTH', 20),
            'critical_admin_passwords' => [
                'admin',
                'admin123',
                'admin12345',
                'password',
                'password123',
                'changeme',
            ],
        ],
    ],

    'logs' => [
        'retention_days' => (int) env('CATMIN_LOG_RETENTION_DAYS', 14),
        'archive_retention_days' => (int) env('CATMIN_LOG_ARCHIVE_RETENTION_DAYS', 90),
    ],

    'alerting' => [
        // Optional email recipient for warning/critical operational alerts.
        'email_to' => env('CATMIN_ALERT_EMAIL_TO', ''),
        // Optional webhook endpoint receiving warning/critical operational alerts.
        'webhook_url' => env('CATMIN_ALERT_WEBHOOK_URL', ''),
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
                        'label' => '2FA',
                        'icon' => 'bi bi-shield-lock',
                        'route' => '2fa.setup',
                        'active_when' => ['2fa.*'],
                        'permission' => 'module.core.list',
                    ],
                    [
                        'label' => 'Sessions',
                        'icon' => 'bi bi-phone',
                        'route' => 'sessions.index',
                        'active_when' => ['sessions.*'],
                        'permission' => 'module.core.list',
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
                        'label' => 'Monitoring',
                        'icon' => 'bi bi-activity',
                        'route' => 'monitoring.index',
                        'active_when' => ['monitoring.*'],
                        'module' => 'logger',
                        'permission' => 'module.logger.list',
                    ],
                    [
                        'label' => 'Performance',
                        'icon' => 'bi bi-speedometer2',
                        'route' => 'performance.index',
                        'active_when' => ['performance.*'],
                        'module' => 'logger',
                        'permission' => 'module.logger.list',
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
                        [
                            'label' => 'Documentation',
                            'icon' => 'bi bi-book',
                            'route' => 'docs.index',
                            'active_when' => ['docs.*'],
                            'module' => 'docs',
                            'permission' => 'module.docs.list',
                        ],
                ],
            ],
            [
                'title' => 'Intégrations',
                'items' => [
                    [
                        'label' => 'Mailer',
                        'icon' => 'bi bi-envelope',
                        'route' => 'mailer.manage',
                        'active_when' => ['mailer.*'],
                        'module' => 'mailer',
                        'permission' => 'module.mailer.menu',
                    ],
                    [
                        'label' => 'Webhooks',
                        'icon' => 'bi bi-send',
                        'route' => 'webhooks.index',
                        'active_when' => ['webhooks.*'],
                        'module' => 'webhooks',
                        'permission' => 'module.webhooks.menu',
                    ],
                    [
                        'label' => 'Marketplace Addons',
                        'icon' => 'bi bi-bag',
                        'route' => 'addons.marketplace.index',
                        'active_when' => ['addons.marketplace.*'],
                        'permission' => 'module.core.config',
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
                        [
                            'label' => 'Factures — Config',
                            'icon' => 'bi bi-receipt',
                            'route' => 'shop.invoices.settings',
                            'active_when' => ['shop.invoices.settings*'],
                            'module' => 'shop',
                            'permission' => 'module.shop.config',
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
