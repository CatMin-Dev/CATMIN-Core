@extends('admin.layouts.catmin')

@section('page_title', 'Paramètres')

@section('content')
<x-admin.crud.page-header
    title="Paramètres"
    subtitle="Centre de configuration produit — tous les réglages CATMIN."
/>

<div class="catmin-page-body d-grid gap-4">
    @php($canWrite = catmin_can('module.settings.config'))

    <x-admin.crud.flash-messages />

    {{-- Tab nav --}}
    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-site-btn" data-bs-toggle="tab" data-bs-target="#tab-site" type="button" role="tab">
                <i class="bi bi-globe me-1"></i>Site
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-admin-btn" data-bs-toggle="tab" data-bs-target="#tab-admin" type="button" role="tab">
                <i class="bi bi-shield-lock me-1"></i>Admin
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-security-btn" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                <i class="bi bi-lock me-1"></i>Sécurité
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-mailer-btn" data-bs-toggle="tab" data-bs-target="#tab-mailer" type="button" role="tab">
                <i class="bi bi-envelope me-1"></i>Mail
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-shop-btn" data-bs-toggle="tab" data-bs-target="#tab-shop" type="button" role="tab">
                <i class="bi bi-cart me-1"></i>Shop
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-ops-btn" data-bs-toggle="tab" data-bs-target="#tab-ops" type="button" role="tab">
                <i class="bi bi-activity me-1"></i>Ops
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-docs-btn" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">
                <i class="bi bi-book me-1"></i>Docs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-seo-btn" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                <i class="bi bi-search me-1"></i>SEO
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white">

        {{-- ══════════════════════════════════════════════════════
             PANEL: SITE
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="tab-site" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.site') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="site_name">Nom du site <span class="text-danger">*</span></label>
                    <input id="site_name" name="site_name" type="text" class="form-control @error('site_name') is-invalid @enderror"
                        value="{{ old('site_name', $site['site_name']) }}" required maxlength="255" @disabled(!$canWrite)>
                    @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="site_url">URL publique <span class="text-danger">*</span></label>
                    <input id="site_url" name="site_url" type="url" class="form-control @error('site_url') is-invalid @enderror"
                        value="{{ old('site_url', $site['site_url']) }}" required maxlength="255" @disabled(!$canWrite)>
                    @error('site_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="site_email">Email système</label>
                    <input id="site_email" name="site_email" type="email" class="form-control @error('site_email') is-invalid @enderror"
                        value="{{ old('site_email', $site['site_email']) }}" maxlength="255" @disabled(!$canWrite)>
                    @error('site_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="site_locale">Langue</label>
                    <input id="site_locale" name="site_locale" type="text" class="form-control @error('site_locale') is-invalid @enderror"
                        value="{{ old('site_locale', $site['site_locale']) }}" maxlength="10" placeholder="fr" @disabled(!$canWrite)>
                    @error('site_locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="site_timezone">Timezone</label>
                    <input id="site_timezone" name="site_timezone" type="text" class="form-control @error('site_timezone') is-invalid @enderror"
                        value="{{ old('site_timezone', $site['site_timezone']) }}" maxlength="60" placeholder="UTC" @disabled(!$canWrite)>
                    @error('site_timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="site_frontend_enabled" name="site_frontend_enabled" value="1"
                            @checked(old('site_frontend_enabled', $site['site_frontend_enabled'])) @disabled(!$canWrite)>
                        <label class="form-check-label" for="site_frontend_enabled">Frontend public activé</label>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="site_registration_open" name="site_registration_open" value="1"
                            @checked(old('site_registration_open', $site['site_registration_open'])) @disabled(!$canWrite)>
                        <label class="form-check-label" for="site_registration_open">Inscriptions publiques ouvertes</label>
                    </div>
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @else
                        <p class="text-muted small mb-0">Permission <code>module.settings.config</code> requise pour modifier.</p>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: ADMIN
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-admin" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.admin') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="admin_path">Chemin admin <span class="text-danger">*</span></label>
                    <input id="admin_path" name="admin_path" type="text" class="form-control font-monospace @error('admin_path') is-invalid @enderror"
                        value="{{ old('admin_path', $admin['admin_path']) }}" required maxlength="80" @disabled(!$canWrite)>
                    <div class="form-text">Sans slash initial. Ex: <code>admin</code></div>
                    @error('admin_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="admin_theme">Thème admin</label>
                    <input id="admin_theme" name="admin_theme" type="text" class="form-control @error('admin_theme') is-invalid @enderror"
                        value="{{ old('admin_theme', $admin['admin_theme']) }}" maxlength="80" @disabled(!$canWrite)>
                    @error('admin_theme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="admin_session_timeout">Timeout session (minutes)</label>
                    <input id="admin_session_timeout" name="admin_session_timeout" type="number" min="5" max="1440"
                        class="form-control @error('admin_session_timeout') is-invalid @enderror"
                        value="{{ old('admin_session_timeout', $admin['admin_session_timeout']) }}" @disabled(!$canWrite)>
                    @error('admin_session_timeout')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="admin_logs_per_page">Logs par page (défaut)</label>
                    <select id="admin_logs_per_page" name="admin_logs_per_page" class="form-select" @disabled(!$canWrite)>
                        @foreach([20, 50, 100, 250] as $n)
                            <option value="{{ $n }}" @selected(old('admin_logs_per_page', $admin['admin_logs_per_page']) == $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    @error('admin_logs_per_page')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: SÉCURITÉ
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-security" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.security') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="security_login_lock_attempts">Max tentatives login</label>
                    <input id="security_login_lock_attempts" name="security_login_lock_attempts" type="number" min="1" max="20"
                        class="form-control @error('security_login_lock_attempts') is-invalid @enderror"
                        value="{{ old('security_login_lock_attempts', $security['security_login_lock_attempts']) }}" @disabled(!$canWrite)>
                    @error('security_login_lock_attempts')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="security_login_lock_minutes">Durée blocage (minutes)</label>
                    <input id="security_login_lock_minutes" name="security_login_lock_minutes" type="number" min="1" max="1440"
                        class="form-control @error('security_login_lock_minutes') is-invalid @enderror"
                        value="{{ old('security_login_lock_minutes', $security['security_login_lock_minutes']) }}" @disabled(!$canWrite)>
                    @error('security_login_lock_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="security_password_reset_expiry">Expiration lien reset (minutes)</label>
                    <input id="security_password_reset_expiry" name="security_password_reset_expiry" type="number" min="5" max="1440"
                        class="form-control @error('security_password_reset_expiry') is-invalid @enderror"
                        value="{{ old('security_password_reset_expiry', $security['security_password_reset_expiry']) }}" @disabled(!$canWrite)>
                    @error('security_password_reset_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="security_webhook_nonce_ttl">Nonce webhook TTL (secondes)</label>
                    <input id="security_webhook_nonce_ttl" name="security_webhook_nonce_ttl" type="number" min="60" max="86400"
                        class="form-control @error('security_webhook_nonce_ttl') is-invalid @enderror"
                        value="{{ old('security_webhook_nonce_ttl', $security['security_webhook_nonce_ttl']) }}" @disabled(!$canWrite)>
                    @error('security_webhook_nonce_ttl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="security_api_token_ttl">Token API TTL (minutes)</label>
                    <input id="security_api_token_ttl" name="security_api_token_ttl" type="number" min="5" max="43200"
                        class="form-control @error('security_api_token_ttl') is-invalid @enderror"
                        value="{{ old('security_api_token_ttl', $security['security_api_token_ttl']) }}" @disabled(!$canWrite)>
                    @error('security_api_token_ttl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: MAIL
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-mailer" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.mailer') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="mailer_from_name">Nom expéditeur</label>
                    <input id="mailer_from_name" name="mailer_from_name" type="text" maxlength="191"
                        class="form-control @error('mailer_from_name') is-invalid @enderror"
                        value="{{ old('mailer_from_name', $mailer['mailer_from_name']) }}" @disabled(!$canWrite)>
                    @error('mailer_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="mailer_from_email">Email expéditeur</label>
                    <input id="mailer_from_email" name="mailer_from_email" type="email" maxlength="191"
                        class="form-control @error('mailer_from_email') is-invalid @enderror"
                        value="{{ old('mailer_from_email', $mailer['mailer_from_email']) }}" @disabled(!$canWrite)>
                    @error('mailer_from_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="mailer_reply_to">Reply-to (optionnel)</label>
                    <input id="mailer_reply_to" name="mailer_reply_to" type="email" maxlength="191"
                        class="form-control @error('mailer_reply_to') is-invalid @enderror"
                        value="{{ old('mailer_reply_to', $mailer['mailer_reply_to']) }}" @disabled(!$canWrite)>
                    @error('mailer_reply_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="mailer_signature">Signature email</label>
                    <textarea id="mailer_signature" name="mailer_signature" rows="3"
                        class="form-control @error('mailer_signature') is-invalid @enderror" @disabled(!$canWrite)>{{ old('mailer_signature', $mailer['mailer_signature']) }}</textarea>
                    @error('mailer_signature')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="mailer_dry_run" name="mailer_dry_run" value="1"
                            @checked(old('mailer_dry_run', $mailer['mailer_dry_run'])) @disabled(!$canWrite)>
                        <label class="form-check-label" for="mailer_dry_run">
                            Mode dry-run (aucun email envoyé)
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: SHOP
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-shop" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.shop') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="shop_currency">Devise (ISO 4217)</label>
                    <input id="shop_currency" name="shop_currency" type="text" maxlength="3"
                        class="form-control font-monospace text-uppercase @error('shop_currency') is-invalid @enderror"
                        value="{{ old('shop_currency', $shop['shop_currency']) }}" placeholder="EUR" @disabled(!$canWrite)>
                    @error('shop_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="shop_invoice_prefix">Préfixe facture</label>
                    <input id="shop_invoice_prefix" name="shop_invoice_prefix" type="text" maxlength="20"
                        class="form-control font-monospace @error('shop_invoice_prefix') is-invalid @enderror"
                        value="{{ old('shop_invoice_prefix', $shop['shop_invoice_prefix']) }}" placeholder="INV-" @disabled(!$canWrite)>
                    @error('shop_invoice_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="shop_default_order_status">Statut commande par défaut</label>
                    <select id="shop_default_order_status" name="shop_default_order_status" class="form-select" @disabled(!$canWrite)>
                        @foreach(['pending', 'confirmed', 'processing', 'on_hold'] as $s)
                            <option value="{{ $s }}" @selected(old('shop_default_order_status', $shop['shop_default_order_status']) === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="shop_email_confirmation">Email confirmation commande</label>
                    <input id="shop_email_confirmation" name="shop_email_confirmation" type="email" maxlength="191"
                        class="form-control @error('shop_email_confirmation') is-invalid @enderror"
                        value="{{ old('shop_email_confirmation', $shop['shop_email_confirmation']) }}" @disabled(!$canWrite)>
                    @error('shop_email_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="shop_stock_out_behavior">Comportement rupture de stock</label>
                    <select id="shop_stock_out_behavior" name="shop_stock_out_behavior" class="form-select" @disabled(!$canWrite)>
                        <option value="block" @selected(old('shop_stock_out_behavior', $shop['shop_stock_out_behavior']) === 'block')>Bloquer l'achat</option>
                        <option value="allow" @selected(old('shop_stock_out_behavior', $shop['shop_stock_out_behavior']) === 'allow')>Autoriser</option>
                        <option value="backorder" @selected(old('shop_stock_out_behavior', $shop['shop_stock_out_behavior']) === 'backorder')>Backorder</option>
                    </select>
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: OPS
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-ops" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.ops') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12">
                    <div class="alert alert-info py-2 small mb-0">
                        Ces paramètres pilotent les alertes système et la rétention des logs.
                        Ils sont lus en temps réel par <code>AlertingService</code> et <code>LogMaintenanceService</code>.
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="ops_alert_email">Email cible alertes</label>
                    <input id="ops_alert_email" name="ops_alert_email" type="email" maxlength="191"
                        class="form-control @error('ops_alert_email') is-invalid @enderror"
                        value="{{ old('ops_alert_email', $ops['ops_alert_email']) }}" @disabled(!$canWrite)>
                    @error('ops_alert_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="ops_alert_webhook_url">Webhook URL alertes</label>
                    <input id="ops_alert_webhook_url" name="ops_alert_webhook_url" type="url" maxlength="500"
                        class="form-control font-monospace @error('ops_alert_webhook_url') is-invalid @enderror"
                        value="{{ old('ops_alert_webhook_url', $ops['ops_alert_webhook_url']) }}" @disabled(!$canWrite)>
                    @error('ops_alert_webhook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="ops_log_retention_days">Rétention logs (jours)</label>
                    <input id="ops_log_retention_days" name="ops_log_retention_days" type="number" min="1" max="365"
                        class="form-control @error('ops_log_retention_days') is-invalid @enderror"
                        value="{{ old('ops_log_retention_days', $ops['ops_log_retention_days']) }}" @disabled(!$canWrite)>
                    @error('ops_log_retention_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="ops_log_archive_days">Rétention archives (jours)</label>
                    <input id="ops_log_archive_days" name="ops_log_archive_days" type="number" min="7" max="3650"
                        class="form-control @error('ops_log_archive_days') is-invalid @enderror"
                        value="{{ old('ops_log_archive_days', $ops['ops_log_archive_days']) }}" @disabled(!$canWrite)>
                    @error('ops_log_archive_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="ops_failed_jobs_threshold">Seuil failed jobs</label>
                    <input id="ops_failed_jobs_threshold" name="ops_failed_jobs_threshold" type="number" min="1" max="1000"
                        class="form-control @error('ops_failed_jobs_threshold') is-invalid @enderror"
                        value="{{ old('ops_failed_jobs_threshold', $ops['ops_failed_jobs_threshold']) }}" @disabled(!$canWrite)>
                    @error('ops_failed_jobs_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label" for="ops_webhook_failures_threshold">Seuil échecs webhook</label>
                    <input id="ops_webhook_failures_threshold" name="ops_webhook_failures_threshold" type="number" min="1" max="100"
                        class="form-control @error('ops_webhook_failures_threshold') is-invalid @enderror"
                        value="{{ old('ops_webhook_failures_threshold', $ops['ops_webhook_failures_threshold']) }}" @disabled(!$canWrite)>
                    @error('ops_webhook_failures_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: DOCS
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-docs" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.docs') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="docs_enabled" name="docs_enabled" value="1"
                            @checked(old('docs_enabled', $docs['docs_enabled'])) @disabled(!$canWrite)>
                        <label class="form-check-label" for="docs_enabled">Centre d'aide activé</label>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="docs_local_source">Source locale des docs</label>
                    <input id="docs_local_source" name="docs_local_source" type="text" maxlength="191"
                        class="form-control font-monospace @error('docs_local_source') is-invalid @enderror"
                        value="{{ old('docs_local_source', $docs['docs_local_source']) }}" placeholder="docs-site" @disabled(!$canWrite)>
                    <div class="form-text">Chemin relatif depuis la racine du projet.</div>
                    @error('docs_local_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="docs_discord_publish_enabled" name="docs_discord_publish_enabled" value="1"
                            @checked(old('docs_discord_publish_enabled', $docs['docs_discord_publish_enabled'])) @disabled(!$canWrite)>
                        <label class="form-check-label" for="docs_discord_publish_enabled">Publication Discord activée</label>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label" for="docs_discord_webhook_url">Webhook Discord</label>
                    <input id="docs_discord_webhook_url" name="docs_discord_webhook_url" type="url" maxlength="500"
                        class="form-control @error('docs_discord_webhook_url') is-invalid @enderror"
                        value="{{ old('docs_discord_webhook_url', $docs['docs_discord_webhook_url']) }}"
                        placeholder="https://discord.com/api/webhooks/..." @disabled(!$canWrite)>
                    @error('docs_discord_webhook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="docs_discord_username">Nom bot Discord</label>
                    <input id="docs_discord_username" name="docs_discord_username" type="text" maxlength="80"
                        class="form-control @error('docs_discord_username') is-invalid @enderror"
                        value="{{ old('docs_discord_username', $docs['docs_discord_username']) }}" placeholder="CATMIN Docs" @disabled(!$canWrite)>
                    @error('docs_discord_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════
             PANEL: SEO
        ══════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-seo" role="tabpanel">
            <form method="POST" action="{{ admin_route('settings.update.seo') }}" class="row g-3">
                @csrf @method('PUT')

                <div class="col-12 col-lg-4">
                    <label class="form-label" for="seo_sitemap_cache_minutes">Cache sitemap (minutes)</label>
                    <input id="seo_sitemap_cache_minutes" name="seo_sitemap_cache_minutes" type="number" min="5" max="1440"
                        class="form-control @error('seo_sitemap_cache_minutes') is-invalid @enderror"
                        value="{{ old('seo_sitemap_cache_minutes', $seo['seo_sitemap_cache_minutes']) }}" @disabled(!$canWrite)>
                    @error('seo_sitemap_cache_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-8 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="seo_sitemap_auto_refresh" name="seo_sitemap_auto_refresh" value="1"
                            @checked(old('seo_sitemap_auto_refresh', $seo['seo_sitemap_auto_refresh'])) @disabled(!$canWrite)>
                        <label class="form-check-label" for="seo_sitemap_auto_refresh">Rafraichissement automatique via cron</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="seo_robots_txt">Contenu robots.txt</label>
                    <textarea id="seo_robots_txt" name="seo_robots_txt" rows="12"
                        class="form-control font-monospace @error('seo_robots_txt') is-invalid @enderror" @disabled(!$canWrite)>{{ old('seo_robots_txt', $seo['seo_robots_txt']) }}</textarea>
                    <div class="form-text">Directives autorisees: User-agent, Allow, Disallow, Sitemap, Crawl-delay, Host, commentaires (#).</div>
                    @error('seo_robots_txt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex gap-2 flex-wrap">
                    @if($canWrite)
                        <button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Enregistrer</button>
                    @endif

                    @if(catmin_can('module.seo.edit'))
                        <button class="btn btn-outline-secondary" type="submit" formaction="{{ admin_route('seo.sitemap.refresh') }}" formmethod="post">
                            <i class="bi bi-arrow-repeat me-1"></i>Regenerer sitemap maintenant
                        </button>
                    @endif
                </div>
            </form>
        </div>

    </div>

    {{-- All settings table --}}
    <x-admin.crud.table-card
        title="Valeurs enregistrées"
        :count="$allSettings->count()"
        :empty-colspan="5"
        empty-message="Aucune valeur enregistrée."
    >
        <x-slot:head>
            <tr>
                <th>Groupe</th>
                <th>Clé</th>
                <th>Label</th>
                <th>Valeur</th>
                <th>Type</th>
                <th>Public</th>
            </tr>
        </x-slot:head>
        <x-slot:rows>
            @foreach($allSettings as $setting)
                <tr>
                    <td><span class="badge bg-secondary">{{ $setting->group ?: 'general' }}</span></td>
                    <td><code>{{ $setting->key }}</code></td>
                    <td>{{ $setting->label }}</td>
                    <td class="text-truncate" style="max-width:220px">
                        {{ is_scalar($setting->value) ? $setting->value : json_encode($setting->value) }}
                    </td>
                    <td>{{ $setting->type }}</td>
                    <td>
                        @if($setting->is_public)
                            <span class="badge bg-success">public</span>
                        @else
                            <span class="badge bg-light text-dark border">privé</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:rows>
    </x-admin.crud.table-card>
</div>

{{-- Re-open the right tab if there were validation errors --}}
@if ($errors->any())
<script>
(function () {
    var panels = {
        'site_name': 'tab-site', 'site_url': 'tab-site', 'site_email': 'tab-site',
        'site_locale': 'tab-site', 'site_timezone': 'tab-site',
        'admin_path': 'tab-admin', 'admin_theme': 'tab-admin',
        'admin_session_timeout': 'tab-admin', 'admin_logs_per_page': 'tab-admin',
        'security_login_lock_attempts': 'tab-security', 'security_login_lock_minutes': 'tab-security',
        'security_password_reset_expiry': 'tab-security',
        'security_webhook_nonce_ttl': 'tab-security', 'security_api_token_ttl': 'tab-security',
        'mailer_from_name': 'tab-mailer', 'mailer_from_email': 'tab-mailer',
        'mailer_reply_to': 'tab-mailer', 'mailer_signature': 'tab-mailer',
        'shop_currency': 'tab-shop', 'shop_invoice_prefix': 'tab-shop',
        'ops_alert_email': 'tab-ops', 'ops_alert_webhook_url': 'tab-ops',
        'ops_log_retention_days': 'tab-ops',
        'docs_enabled': 'tab-docs', 'docs_local_source': 'tab-docs',
        'docs_discord_publish_enabled': 'tab-docs', 'docs_discord_webhook_url': 'tab-docs', 'docs_discord_username': 'tab-docs',
        'seo_sitemap_cache_minutes': 'tab-seo', 'seo_sitemap_auto_refresh': 'tab-seo', 'seo_robots_txt': 'tab-seo',
    };
    var errorKeys = @json(array_keys($errors->toArray()));
    for (var i = 0; i < errorKeys.length; i++) {
        var target = panels[errorKeys[i]];
        if (target) {
            var el = document.getElementById(target + '-btn');
            if (el) { el.click(); break; }
        }
    }
}());
</script>
@endif
@endsection
