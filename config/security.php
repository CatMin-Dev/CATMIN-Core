<?php

declare(strict_types=1);

return [
    'admin_path' => 'admin',
    'admin_session_name' => 'CATMIN_ADMIN_SESSID',
    'install_session_name' => 'CATMIN_INSTALL_SESSID',
    'session_cookie_secure' => null,
    'admin_reauth_required' => true,
    'reauth_ttl_seconds' => 900,
    'session_lifetime' => 7200,
    'bind_session_fingerprint' => true,
    'csrf_rotate_on_validation' => true,
    'superadmin_email_reset' => false,
    'admin_password_min' => 12,
    'admin_password_require_upper' => true,
    'admin_password_require_lower' => true,
    'admin_password_require_digit' => true,
    'admin_password_require_symbol' => true,
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
