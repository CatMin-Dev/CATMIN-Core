<?php

declare(strict_types=1);

final class CoreSettingsSchema
{
    public static function defaults(): array
    {
        return [
            'general.app_name' => ['group' => 'general', 'type' => 'string', 'default' => (string) env('APP_NAME', 'CATMIN'), 'autoload' => true, 'protected' => false, 'system' => true],
            'general.app_env' => ['group' => 'general', 'type' => 'enum', 'enum' => ['production', 'staging', 'development'], 'default' => (string) config('app.env', 'production'), 'autoload' => true, 'protected' => true, 'system' => true],
            'general.timezone' => ['group' => 'general', 'type' => 'string', 'default' => (string) config('app.timezone', 'UTC'), 'autoload' => true, 'protected' => false, 'system' => true],
            'general.admin_path' => ['group' => 'general', 'type' => 'path', 'default' => (string) config('security.admin_path', 'admin'), 'autoload' => true, 'protected' => true, 'system' => true],

            'ui.theme_default' => ['group' => 'ui', 'type' => 'enum', 'enum' => ['light', 'dark', 'corporate'], 'default' => 'corporate', 'autoload' => true, 'protected' => false, 'system' => false],
            'ui.table_density' => ['group' => 'ui', 'type' => 'enum', 'enum' => ['compact', 'comfortable', 'spacious'], 'default' => 'comfortable', 'autoload' => true, 'protected' => false, 'system' => false],
            'ui.compact_sidebar' => ['group' => 'ui', 'type' => 'bool', 'default' => true, 'autoload' => true, 'protected' => false, 'system' => false],
            'ui.show_debug' => ['group' => 'ui', 'type' => 'bool', 'default' => false, 'autoload' => true, 'protected' => false, 'system' => false],

            'mail.enabled' => ['group' => 'mail', 'type' => 'bool', 'default' => false, 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.driver' => ['group' => 'mail', 'type' => 'enum', 'enum' => ['smtp', 'sendmail', 'mailgun'], 'default' => 'smtp', 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.from_name' => ['group' => 'mail', 'type' => 'string', 'default' => 'CATMIN', 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.from_email' => ['group' => 'mail', 'type' => 'email', 'default' => 'noreply@example.com', 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.host' => ['group' => 'mail', 'type' => 'string', 'default' => '', 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.port' => ['group' => 'mail', 'type' => 'int', 'default' => 587, 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.encryption' => ['group' => 'mail', 'type' => 'enum', 'enum' => ['tls', 'ssl', 'none'], 'default' => 'tls', 'autoload' => false, 'protected' => false, 'system' => false],
            'mail.username' => ['group' => 'mail', 'type' => 'string', 'default' => '', 'autoload' => false, 'protected' => false, 'system' => false],

            'security.session_minutes' => ['group' => 'security', 'type' => 'int', 'default' => 120, 'autoload' => true, 'protected' => false, 'system' => true],
            'security.max_attempts' => ['group' => 'security', 'type' => 'int', 'default' => 5, 'autoload' => true, 'protected' => false, 'system' => true],
            'security.password_min' => ['group' => 'security', 'type' => 'int', 'default' => 12, 'autoload' => true, 'protected' => false, 'system' => true],
            'security.enforce_2fa' => ['group' => 'security', 'type' => 'bool', 'default' => false, 'autoload' => true, 'protected' => false, 'system' => true],
            'security.ip_whitelist_enabled' => ['group' => 'security', 'type' => 'bool', 'default' => false, 'autoload' => true, 'protected' => true, 'system' => true],
            'security.ip_whitelist' => ['group' => 'security', 'type' => 'json', 'default' => [], 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_reauth_required' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.admin_reauth_required', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.reauth_ttl_seconds' => ['group' => 'security', 'type' => 'int', 'default' => (int) config('security.reauth_ttl_seconds', 900), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.session_lifetime' => ['group' => 'security', 'type' => 'int', 'default' => (int) config('security.session_lifetime', 7200), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.bind_session_fingerprint' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.bind_session_fingerprint', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.csrf_rotate_on_validation' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.csrf_rotate_on_validation', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.superadmin_email_reset' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.superadmin_email_reset', false), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_password_min' => ['group' => 'security', 'type' => 'int', 'default' => (int) config('security.admin_password_min', 12), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_password_require_upper' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.admin_password_require_upper', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_password_require_lower' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.admin_password_require_lower', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_password_require_digit' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.admin_password_require_digit', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_password_require_symbol' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.admin_password_require_symbol', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.progressive_lockout' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.progressive_lockout', true), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.lockout_window_minutes' => ['group' => 'security', 'type' => 'int', 'default' => (int) config('security.lockout_window_minutes', 30), 'autoload' => true, 'protected' => true, 'system' => true],
            'security.admin_noindex' => ['group' => 'security', 'type' => 'bool', 'default' => (bool) config('security.admin_noindex', true), 'autoload' => true, 'protected' => true, 'system' => true],

            'maintenance.enabled' => ['group' => 'maintenance', 'type' => 'bool', 'default' => false, 'autoload' => true, 'protected' => false, 'system' => true],
            'maintenance.level' => ['group' => 'maintenance', 'type' => 'int', 'default' => 1, 'autoload' => true, 'protected' => false, 'system' => true],
            'maintenance.reason' => ['group' => 'maintenance', 'type' => 'string', 'default' => '', 'autoload' => true, 'protected' => false, 'system' => true],
            'maintenance.message' => ['group' => 'maintenance', 'type' => 'string', 'default' => 'Maintenance en cours', 'autoload' => true, 'protected' => false, 'system' => true],
            'maintenance.allow_admin' => ['group' => 'maintenance', 'type' => 'bool', 'default' => true, 'autoload' => true, 'protected' => false, 'system' => true],
            'maintenance.allowed_ips' => ['group' => 'maintenance', 'type' => 'string', 'default' => '', 'autoload' => true, 'protected' => true, 'system' => true],
            'maintenance.allowed_admin_ids' => ['group' => 'maintenance', 'type' => 'string', 'default' => '', 'autoload' => true, 'protected' => true, 'system' => true],
            'maintenance.started_at' => ['group' => 'maintenance', 'type' => 'string', 'default' => '', 'autoload' => false, 'protected' => true, 'system' => true],
            'maintenance.enabled_by' => ['group' => 'maintenance', 'type' => 'string', 'default' => '', 'autoload' => false, 'protected' => true, 'system' => true],
            'maintenance.last_backup' => ['group' => 'maintenance', 'type' => 'string', 'default' => '-', 'autoload' => false, 'protected' => true, 'system' => true],
            'maintenance.last_restore' => ['group' => 'maintenance', 'type' => 'string', 'default' => '-', 'autoload' => false, 'protected' => true, 'system' => true],

            'system.cron_enabled' => ['group' => 'system', 'type' => 'bool', 'default' => filter_var((string) env('CRON_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN), 'autoload' => true, 'protected' => true, 'system' => true],
            'backup.local_enabled' => ['group' => 'backup', 'type' => 'bool', 'default' => true, 'autoload' => false, 'protected' => false, 'system' => false],
            'legal.bundle_version' => ['group' => 'legal', 'type' => 'string', 'default' => '1.0.0', 'autoload' => false, 'protected' => false, 'system' => false],
        ];
    }

    public static function groups(): array
    {
        return [
            'general',
            'ui',
            'auth',
            'security',
            'modules',
            'mail',
            'backup',
            'legal',
            'maintenance',
            'system',
        ];
    }
}
