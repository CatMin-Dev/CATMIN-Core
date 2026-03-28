<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Site ─────────────────────────────────────────────────────────
            ['key' => 'site.name',             'value' => 'CATMIN',                    'type' => 'string',  'group' => 'site',     'label' => 'Nom du site',               'description' => 'Nom public du site',                   'is_public' => true],
            ['key' => 'site.url',              'value' => config('app.url'),           'type' => 'string',  'group' => 'site',     'label' => 'URL du site',               'description' => 'URL publique du site',                 'is_public' => true],
            ['key' => 'site.email',            'value' => '',                          'type' => 'email',   'group' => 'site',     'label' => 'Email système',             'description' => 'Email système principal',              'is_public' => false],
            ['key' => 'site.locale',           'value' => config('app.locale', 'fr'), 'type' => 'string',  'group' => 'site',     'label' => 'Langue',                    'description' => 'Langue par défaut',                    'is_public' => true],
            ['key' => 'site.timezone',         'value' => config('app.timezone', 'UTC'),'type' => 'string', 'group' => 'site',    'label' => 'Timezone',                  'description' => 'Fuseau horaire',                       'is_public' => false],
            ['key' => 'site.frontend_enabled', 'value' => '1',                         'type' => 'boolean','group' => 'site',     'label' => 'Frontend activé',           'description' => 'Frontend public actif',                'is_public' => true],
            ['key' => 'site.registration_open','value' => '0',                         'type' => 'boolean','group' => 'site',     'label' => 'Inscriptions ouvertes',     'description' => 'Ouverture inscription publique',       'is_public' => false],

            // ── Admin ─────────────────────────────────────────────────────────
            ['key' => 'admin.path',            'value' => config('catmin.admin.path', 'admin'), 'type' => 'string', 'group' => 'admin', 'label' => 'Chemin admin', 'description' => 'Chemin admin préféré',                 'is_public' => false],
            ['key' => 'admin.theme',           'value' => 'catmin-light',              'type' => 'string',  'group' => 'admin',    'label' => 'Thème admin',               'description' => 'Thème admin préféré',                  'is_public' => false],
            ['key' => 'admin.session_timeout', 'value' => '120',                       'type' => 'integer', 'group' => 'admin',    'label' => 'Timeout session (min)',     'description' => 'Timeout session admin (minutes)',      'is_public' => false],
            ['key' => 'admin.logs_per_page',   'value' => '50',                        'type' => 'integer', 'group' => 'admin',    'label' => 'Logs par page',             'description' => 'Nombre de logs par page par défaut',   'is_public' => false],

            // ── Security ──────────────────────────────────────────────────────
            ['key' => 'security.login_lock_attempts',   'value' => '5',    'type' => 'integer', 'group' => 'security', 'label' => 'Max tentatives login',          'description' => 'Tentatives avant blocage du compte',       'is_public' => false],
            ['key' => 'security.login_lock_minutes',    'value' => '15',   'type' => 'integer', 'group' => 'security', 'label' => 'Durée blocage (min)',            'description' => 'Durée du blocage en minutes',              'is_public' => false],
            ['key' => 'security.password_reset_expiry', 'value' => '60',   'type' => 'integer', 'group' => 'security', 'label' => 'Reset password TTL (min)',       'description' => 'Expiration lien reset mot de passe',       'is_public' => false],
            ['key' => 'security.webhook_nonce_ttl',     'value' => '3600', 'type' => 'integer', 'group' => 'security', 'label' => 'Nonce webhook TTL (s)',          'description' => 'Durée de validité nonce webhook en secondes','is_public' => false],
            ['key' => 'security.api_token_ttl',         'value' => '1440', 'type' => 'integer', 'group' => 'security', 'label' => 'API token TTL (min)',            'description' => 'Durée de validité token API en minutes',    'is_public' => false],

            // ── Mailer ────────────────────────────────────────────────────────
            ['key' => 'mailer.from_name',  'value' => config('mail.from.name', 'CATMIN'),    'type' => 'string',  'group' => 'mailer', 'label' => 'Nom expéditeur',      'description' => 'Nom expéditeur emails transactionnels', 'is_public' => false],
            ['key' => 'mailer.from_email', 'value' => config('mail.from.address', ''),       'type' => 'email',   'group' => 'mailer', 'label' => 'Email expéditeur',    'description' => 'Adresse expéditeur emails',             'is_public' => false],
            ['key' => 'mailer.reply_to',   'value' => '',                                    'type' => 'email',   'group' => 'mailer', 'label' => 'Reply-to',            'description' => 'Adresse reply-to des emails',           'is_public' => false],
            ['key' => 'mailer.signature',  'value' => '',                                    'type' => 'text',    'group' => 'mailer', 'label' => 'Signature email',     'description' => 'Signature ajoutée aux emails',          'is_public' => false],
            ['key' => 'mailer.dry_run',    'value' => '0',                                   'type' => 'boolean', 'group' => 'mailer', 'label' => 'Mode dry-run',        'description' => 'Désactiver l\'envoi réel d\'emails',    'is_public' => false],

            // ── Shop ──────────────────────────────────────────────────────────
            ['key' => 'shop.currency',             'value' => 'EUR',     'type' => 'string', 'group' => 'shop', 'label' => 'Devise',                    'description' => 'Code devise ISO 4217',                'is_public' => true],
            ['key' => 'shop.invoice_prefix',       'value' => 'INV-',   'type' => 'string', 'group' => 'shop', 'label' => 'Préfixe facture',           'description' => 'Préfixe numéro de facture',           'is_public' => false],
            ['key' => 'shop.default_order_status', 'value' => 'pending','type' => 'string', 'group' => 'shop', 'label' => 'Statut commande par défaut','description' => 'Statut commande à la création',       'is_public' => false],
            ['key' => 'shop.email_confirmation',   'value' => '',       'type' => 'email',  'group' => 'shop', 'label' => 'Email confirmation commande','description' => 'Email notifié à chaque nouvelle commande','is_public' => false],
            ['key' => 'shop.stock_out_behavior',   'value' => 'block',  'type' => 'string', 'group' => 'shop', 'label' => 'Comportement rupture stock', 'description' => 'Que faire en rupture de stock',       'is_public' => false],

            // ── Ops ───────────────────────────────────────────────────────────
            ['key' => 'ops.alert_email',                'value' => '', 'type' => 'email',   'group' => 'ops', 'label' => 'Email alertes',               'description' => 'Email cible pour les alertes système', 'is_public' => false],
            ['key' => 'ops.alert_webhook_url',          'value' => '', 'type' => 'url',     'group' => 'ops', 'label' => 'Webhook alertes URL',         'description' => 'URL webhook pour alertes système',      'is_public' => false],
            ['key' => 'ops.log_retention_days',         'value' => '14',  'type' => 'integer', 'group' => 'ops', 'label' => 'Rétention logs (jours)',    'description' => 'Durée rétention logs en base',          'is_public' => false],
            ['key' => 'ops.log_archive_retention_days', 'value' => '90',  'type' => 'integer', 'group' => 'ops', 'label' => 'Rétention archives (jours)','description' => 'Durée rétention archives logs',         'is_public' => false],
            ['key' => 'ops.failed_jobs_threshold',      'value' => '5',   'type' => 'integer', 'group' => 'ops', 'label' => 'Seuil failed jobs',         'description' => 'Nb d\'échecs avant alerte',             'is_public' => false],
            ['key' => 'ops.webhook_failures_threshold', 'value' => '3',   'type' => 'integer', 'group' => 'ops', 'label' => 'Seuil échecs webhook',      'description' => 'Nb d\'échecs webhook avant alerte',     'is_public' => false],

            // ── Docs ──────────────────────────────────────────────────────────
            ['key' => 'docs.enabled',      'value' => '1',        'type' => 'boolean', 'group' => 'docs', 'label' => 'Docs activés',     'description' => 'Centre d\'aide activé', 'is_public' => false],
            ['key' => 'docs.local_source', 'value' => 'docs-site','type' => 'string',  'group' => 'docs', 'label' => 'Source locale',   'description' => 'Répertoire source des docs', 'is_public' => false],
        ];

        foreach ($settings as $s) {
            SettingService::put(
                key:             $s['key'],
                value:           $s['value'],
                type:            $s['type'],
                group:           $s['group'],
                description:     $s['description'],
                isPublic:        $s['is_public'],
                label:           $s['label'] ?? null,
                isEditable:      true,
            );
        }

        $this->command->info('Global settings seeded successfully (' . count($settings) . ' entries).');
    }
}
