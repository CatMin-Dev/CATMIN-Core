<?php

declare(strict_types=1);

return [
    'admin_path' => 'admin',
    'admin_session_name' => 'CATMIN_ADMIN_SESSID',
    'admin_reauth_required' => true,
    'reauth_ttl_seconds' => 900,
    'session_lifetime' => 7200,
    'superadmin_email_reset' => false,
    'ip_whitelist_enabled' => false,
    'ip_whitelist' => [
        '127.0.0.1',
        '::1',
    ],
    'progressive_lockout' => true,
    'lockout_window_minutes' => 30,
    'admin_noindex' => true,
    'csp' => [
        'default-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'frame-ancestors' => ["'none'"],
        'object-src' => ["'none'"],
        'img-src' => ["'self'", 'data:'],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'script-src' => ["'self'"],
        'font-src' => ["'self'", 'data:'],
        'connect-src' => ["'self'"],
        'form-action' => ["'self'"],
    ],
];
