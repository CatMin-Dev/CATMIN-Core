<?php

namespace Modules\Settings\Services;

use App\Models\Setting;
use App\Services\SettingService;

class SettingsAdminService
{
    // ─── Panel: Site / Produit ───────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function sitePanel(): array
    {
        return [
            'site_name'              => (string) SettingService::get('site.name', config('app.name', 'CATMIN')),
            'site_url'               => (string) SettingService::get('site.url', config('app.url')),
            'site_email'             => (string) SettingService::get('site.email', ''),
            'site_locale'            => (string) SettingService::get('site.locale', config('app.locale', 'fr')),
            'site_timezone'          => (string) SettingService::get('site.timezone', config('app.timezone', 'UTC')),
            'site_frontend_enabled'  => $this->toBool(SettingService::get('site.frontend_enabled', true)),
            'site_registration_open' => $this->toBool(SettingService::get('site.registration_open', false)),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateSitePanel(array $payload): void
    {
        SettingService::put('site.name', (string) $payload['site_name'], 'string', 'site', 'Nom public du site', true, 'Nom du site');
        SettingService::put('site.url', (string) $payload['site_url'], 'string', 'site', 'URL publique du site', true, 'URL du site');
        SettingService::put('site.email', (string) $payload['site_email'], 'email', 'site', 'Email système principal', false, 'Email système');
        SettingService::put('site.locale', (string) $payload['site_locale'], 'string', 'site', 'Langue par défaut', true, 'Langue');
        SettingService::put('site.timezone', (string) $payload['site_timezone'], 'string', 'site', 'Fuseau horaire', false, 'Timezone');
        SettingService::put('site.frontend_enabled', $this->toStringBool($payload['site_frontend_enabled']), 'boolean', 'site', 'Frontend public actif', true, 'Frontend activé');
        SettingService::put('site.registration_open', $this->toStringBool($payload['site_registration_open']), 'boolean', 'site', 'Ouverture inscription publique', false, 'Inscriptions ouvertes');
    }

    // ─── Panel: Admin ────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function adminPanel(): array
    {
        return [
            'admin_path'            => (string) SettingService::get('admin.path', config('catmin.admin.path', 'admin')),
            'admin_theme'           => (string) SettingService::get('admin.theme', 'catmin-light'),
            'admin_session_timeout' => (int) SettingService::get('admin.session_timeout', 120),
            'admin_logs_per_page'   => (int) SettingService::get('admin.logs_per_page', 50),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateAdminPanel(array $payload): void
    {
        SettingService::put('admin.path', trim(trim((string) $payload['admin_path']), '/'), 'string', 'admin', 'Chemin admin', false, 'Chemin admin');
        SettingService::put('admin.theme', (string) $payload['admin_theme'], 'string', 'admin', 'Thème admin', false, 'Thème');
        SettingService::put('admin.session_timeout', (int) $payload['admin_session_timeout'], 'integer', 'admin', 'Timeout session (minutes)', false, 'Timeout session (min)');
        SettingService::put('admin.logs_per_page', (int) $payload['admin_logs_per_page'], 'integer', 'admin', 'Logs par page par défaut', false, 'Logs par page');
    }

    // ─── Panel: Sécurité ─────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function securityPanel(): array
    {
        return [
            'security_login_lock_attempts'   => (int) SettingService::get('security.login_lock_attempts', 5),
            'security_login_lock_minutes'    => (int) SettingService::get('security.login_lock_minutes', 15),
            'security_password_reset_expiry' => (int) SettingService::get('security.password_reset_expiry', 60),
            'security_webhook_nonce_ttl'     => (int) SettingService::get('security.webhook_nonce_ttl', 3600),
            'security_api_token_ttl'         => (int) SettingService::get('security.api_token_ttl', 1440),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateSecurityPanel(array $payload): void
    {
        SettingService::put('security.login_lock_attempts', (int) $payload['security_login_lock_attempts'], 'integer', 'security', 'Tentatives avant blocage', false, 'Max tentatives login');
        SettingService::put('security.login_lock_minutes', (int) $payload['security_login_lock_minutes'], 'integer', 'security', 'Durée blocage (minutes)', false, 'Durée blocage (min)');
        SettingService::put('security.password_reset_expiry', (int) $payload['security_password_reset_expiry'], 'integer', 'security', 'Expiration lien reset (minutes)', false, 'Reset password TTL (min)');
        SettingService::put('security.webhook_nonce_ttl', (int) $payload['security_webhook_nonce_ttl'], 'integer', 'security', 'TTL nonce webhook (secondes)', false, 'Nonce webhook TTL (s)');
        SettingService::put('security.api_token_ttl', (int) $payload['security_api_token_ttl'], 'integer', 'security', 'Durée validité token API (minutes)', false, 'API token TTL (min)');
    }

    // ─── Panel: Mailer ───────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function mailerPanel(): array
    {
        return [
            'mailer_from_name'  => (string) SettingService::get('mailer.from_name', config('mail.from.name', '')),
            'mailer_from_email' => (string) SettingService::get('mailer.from_email', config('mail.from.address', '')),
            'mailer_reply_to'   => (string) SettingService::get('mailer.reply_to', ''),
            'mailer_signature'  => (string) SettingService::get('mailer.signature', ''),
            'mailer_dry_run'    => $this->toBool(SettingService::get('mailer.dry_run', false)),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateMailerPanel(array $payload): void
    {
        SettingService::put('mailer.from_name', (string) $payload['mailer_from_name'], 'string', 'mailer', 'Nom expéditeur emails', false, 'Nom expéditeur');
        SettingService::put('mailer.from_email', (string) $payload['mailer_from_email'], 'email', 'mailer', 'Adresse expéditeur emails', false, 'Email expéditeur');
        SettingService::put('mailer.reply_to', (string) $payload['mailer_reply_to'], 'email', 'mailer', 'Adresse reply-to', false, 'Reply-to');
        SettingService::put('mailer.signature', (string) $payload['mailer_signature'], 'text', 'mailer', 'Signature email', false, 'Signature');
        SettingService::put('mailer.dry_run', $this->toStringBool($payload['mailer_dry_run']), 'boolean', 'mailer', 'Mode dry-run (pas d\'envoi)', false, 'Mode dry-run');
    }

    // ─── Panel: Shop ─────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function shopPanel(): array
    {
        return [
            'shop_currency'              => (string) SettingService::get('shop.currency', 'EUR'),
            'shop_invoice_prefix'        => (string) SettingService::get('shop.invoice_prefix', 'INV-'),
            'shop_default_order_status'  => (string) SettingService::get('shop.default_order_status', 'pending'),
            'shop_email_confirmation'    => (string) SettingService::get('shop.email_confirmation', ''),
            'shop_stock_out_behavior'    => (string) SettingService::get('shop.stock_out_behavior', 'block'),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateShopPanel(array $payload): void
    {
        SettingService::put('shop.currency', strtoupper((string) $payload['shop_currency']), 'string', 'shop', 'Code devise (ISO 4217)', false, 'Devise');
        SettingService::put('shop.invoice_prefix', (string) $payload['shop_invoice_prefix'], 'string', 'shop', 'Préfixe numéro de facture', false, 'Préfixe facture');
        SettingService::put('shop.default_order_status', (string) $payload['shop_default_order_status'], 'string', 'shop', 'Statut commande par défaut', false, 'Statut commande par défaut');
        SettingService::put('shop.email_confirmation', (string) $payload['shop_email_confirmation'], 'email', 'shop', 'Email de confirmation commande', false, 'Email confirmation commande');
        SettingService::put('shop.stock_out_behavior', (string) $payload['shop_stock_out_behavior'], 'string', 'shop', 'Comportement rupture de stock', false, 'Comportement rupture stock');
    }

    // ─── Panel: Ops / Observabilité ──────────────────────────────────────────

    /** @return array<string, mixed> */
    public function opsPanel(): array
    {
        return [
            'ops_alert_email'                => (string) SettingService::get('ops.alert_email', config('catmin.alerting.email_to', '')),
            'ops_alert_webhook_url'          => (string) SettingService::get('ops.alert_webhook_url', config('catmin.alerting.webhook_url', '')),
            'ops_log_retention_days'         => (int) SettingService::get('ops.log_retention_days', config('catmin.logs.retention_days', 14)),
            'ops_log_archive_days'           => (int) SettingService::get('ops.log_archive_retention_days', config('catmin.logs.archive_retention_days', 90)),
            'ops_failed_jobs_threshold'      => (int) SettingService::get('ops.failed_jobs_threshold', 5),
            'ops_webhook_failures_threshold' => (int) SettingService::get('ops.webhook_failures_threshold', 3),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateOpsPanel(array $payload): void
    {
        SettingService::put('ops.alert_email', (string) $payload['ops_alert_email'], 'email', 'ops', 'Email cible alertes système', false, 'Email alertes');
        SettingService::put('ops.alert_webhook_url', (string) $payload['ops_alert_webhook_url'], 'url', 'ops', 'Webhook URL pour alertes système', false, 'Webhook alertes URL');
        SettingService::put('ops.log_retention_days', (int) $payload['ops_log_retention_days'], 'integer', 'ops', 'Rétention logs (jours)', false, 'Rétention logs (jours)');
        SettingService::put('ops.log_archive_retention_days', (int) $payload['ops_log_archive_days'], 'integer', 'ops', 'Rétention archives logs (jours)', false, 'Rétention archives (jours)');
        SettingService::put('ops.failed_jobs_threshold', (int) $payload['ops_failed_jobs_threshold'], 'integer', 'ops', 'Seuil jobs en échec avant alerte', false, 'Seuil failed jobs');
        SettingService::put('ops.webhook_failures_threshold', (int) $payload['ops_webhook_failures_threshold'], 'integer', 'ops', 'Seuil échecs webhook avant alerte', false, 'Seuil échecs webhook');
    }

    // ─── Panel: Docs ─────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function docsPanel(): array
    {
        return [
            'docs_enabled'      => $this->toBool(SettingService::get('docs.enabled', true)),
            'docs_local_source' => (string) SettingService::get('docs.local_source', 'docs-site'),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function updateDocsPanel(array $payload): void
    {
        SettingService::put('docs.enabled', $this->toStringBool($payload['docs_enabled']), 'boolean', 'docs', 'Centre d\'aide activé', false, 'Docs activés');
        SettingService::put('docs.local_source', (string) $payload['docs_local_source'], 'string', 'docs', 'Source locale des docs', false, 'Source locale');
    }

    // ─── Legacy (backward compat) ────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function essentials(): array
    {
        return array_merge($this->sitePanel(), $this->adminPanel());
    }

    /** @param array<string, mixed> $payload */
    public function updateEssentials(array $payload): void
    {
        $this->updateSitePanel($payload);
        $this->updateAdminPanel($payload);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Setting> */
    public function recentSettings()
    {
        return Setting::query()->orderBy('group')->orderBy('key')->get();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function toStringBool(mixed $value): string
    {
        return $this->toBool($value) ? '1' : '0';
    }
}
