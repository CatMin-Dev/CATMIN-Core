<?php

namespace Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Settings\Services\SettingsAdminService;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsAdminService $service)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Settings/Views/index.blade.php'), [
            'currentPage' => 'settings',
            'site'        => $this->service->sitePanel(),
            'admin'       => $this->service->adminPanel(),
            'security'    => $this->service->securityPanel(),
            'mailer'      => $this->service->mailerPanel(),
            'shop'        => $this->service->shopPanel(),
            'ops'         => $this->service->opsPanel(),
            'docs'        => $this->service->docsPanel(),
            'allSettings' => $this->service->recentSettings(),
            // legacy keys still expected by some other views
            'essentials'     => $this->service->essentials(),
            'trackedSettings' => $this->service->recentSettings(),
        ]);
    }

    // ─── Legacy combined update (still works) ───────────────────────────────

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'              => ['required', 'string', 'max:255'],
            'site_url'               => ['required', 'url', 'max:255'],
            'admin_path'             => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\/_-]+$/'],
            'admin_theme'            => ['required', 'string', 'max:80'],
            'site_frontend_enabled'  => ['nullable', 'boolean'],
            'site_registration_open' => ['nullable', 'boolean'],
        ]);

        $validated['site_frontend_enabled']  = $request->boolean('site_frontend_enabled');
        $validated['site_registration_open'] = $request->boolean('site_registration_open');

        $this->service->updateEssentials($validated);

        return redirect()->route('admin.settings.manage')->with('status', 'Paramètres enregistrés.');
    }

    // ─── Panel: Site ────────────────────────────────────────────────────────

    public function updateSite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'              => ['required', 'string', 'max:255'],
            'site_url'               => ['required', 'url', 'max:255'],
            'site_email'             => ['nullable', 'email', 'max:191'],
            'site_locale'            => ['nullable', 'string', 'max:10'],
            'site_timezone'          => ['nullable', 'string', 'max:60'],
            'site_frontend_enabled'  => ['nullable', 'boolean'],
            'site_registration_open' => ['nullable', 'boolean'],
        ]);

        $validated['site_frontend_enabled']  = $request->boolean('site_frontend_enabled');
        $validated['site_registration_open'] = $request->boolean('site_registration_open');

        $this->service->updateSitePanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-site'])->with('status', 'Paramètres site enregistrés.');
    }

    // ─── Panel: Admin ────────────────────────────────────────────────────────

    public function updateAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_path'            => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\/_-]+$/'],
            'admin_theme'           => ['required', 'string', 'max:80'],
            'admin_session_timeout' => ['required', 'integer', 'min:5', 'max:1440'],
            'admin_logs_per_page'   => ['required', 'integer', 'in:20,50,100,250'],
        ]);

        $this->service->updateAdminPanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-admin'])->with('status', 'Paramètres admin enregistrés.');
    }

    // ─── Panel: Sécurité ─────────────────────────────────────────────────────

    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'security_login_lock_attempts'   => ['required', 'integer', 'min:1', 'max:20'],
            'security_login_lock_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'security_password_reset_expiry' => ['required', 'integer', 'min:5', 'max:1440'],
            'security_webhook_nonce_ttl'     => ['required', 'integer', 'min:60', 'max:86400'],
            'security_api_token_ttl'         => ['required', 'integer', 'min:5', 'max:43200'],
        ]);

        $this->service->updateSecurityPanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-security'])->with('status', 'Paramètres sécurité enregistrés.');
    }

    // ─── Panel: Mailer ───────────────────────────────────────────────────────

    public function updateMailer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mailer_from_name'  => ['nullable', 'string', 'max:191'],
            'mailer_from_email' => ['nullable', 'email', 'max:191'],
            'mailer_reply_to'   => ['nullable', 'email', 'max:191'],
            'mailer_signature'  => ['nullable', 'string', 'max:2000'],
            'mailer_dry_run'    => ['nullable', 'boolean'],
        ]);

        $validated['mailer_dry_run'] = $request->boolean('mailer_dry_run');
        $validated['mailer_from_name'] = $validated['mailer_from_name'] ?? '';
        $validated['mailer_from_email'] = $validated['mailer_from_email'] ?? '';
        $validated['mailer_reply_to'] = $validated['mailer_reply_to'] ?? '';
        $validated['mailer_signature'] = $validated['mailer_signature'] ?? '';

        $this->service->updateMailerPanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-mailer'])->with('status', 'Paramètres mail enregistrés.');
    }

    // ─── Panel: Shop ─────────────────────────────────────────────────────────

    public function updateShop(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_currency'             => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/i'],
            'shop_invoice_prefix'       => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'shop_default_order_status' => ['required', 'string', 'in:pending,confirmed,processing,on_hold'],
            'shop_email_confirmation'   => ['nullable', 'email', 'max:191'],
            'shop_stock_out_behavior'   => ['required', 'string', 'in:block,allow,backorder'],
        ]);

        $validated['shop_email_confirmation'] = $validated['shop_email_confirmation'] ?? '';

        $this->service->updateShopPanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-shop'])->with('status', 'Paramètres shop enregistrés.');
    }

    // ─── Panel: Ops ──────────────────────────────────────────────────────────

    public function updateOps(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ops_alert_email'                => ['nullable', 'email', 'max:191'],
            'ops_alert_webhook_url'          => ['nullable', 'url', 'max:500'],
            'ops_log_retention_days'         => ['required', 'integer', 'min:1', 'max:365'],
            'ops_log_archive_days'           => ['required', 'integer', 'min:7', 'max:3650'],
            'ops_failed_jobs_threshold'      => ['required', 'integer', 'min:1', 'max:1000'],
            'ops_webhook_failures_threshold' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $validated['ops_alert_email']       = $validated['ops_alert_email'] ?? '';
        $validated['ops_alert_webhook_url'] = $validated['ops_alert_webhook_url'] ?? '';

        $this->service->updateOpsPanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-ops'])->with('status', 'Paramètres ops enregistrés.');
    }

    // ─── Panel: Docs ─────────────────────────────────────────────────────────

    public function updateDocs(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'docs_enabled' => ['nullable', 'boolean'],
            'docs_local_source' => ['nullable', 'string', 'max:191'],
            'docs_discord_publish_enabled' => ['nullable', 'boolean'],
            'docs_discord_webhook_url' => ['nullable', 'url', 'max:500'],
            'docs_discord_username' => ['nullable', 'string', 'max:80'],
        ]);

        $validated['docs_enabled'] = $request->boolean('docs_enabled');
        $validated['docs_local_source'] = $validated['docs_local_source'] ?? 'docs-site';
        $validated['docs_discord_publish_enabled'] = $request->boolean('docs_discord_publish_enabled');
        $validated['docs_discord_webhook_url'] = $validated['docs_discord_webhook_url'] ?? '';
        $validated['docs_discord_username'] = $validated['docs_discord_username'] ?? 'CATMIN Docs';

        $this->service->updateDocsPanel($validated);

        return redirect()->route('admin.settings.manage', ['#tab-docs'])->with('status', 'Paramètres docs enregistrés.');
    }
}
