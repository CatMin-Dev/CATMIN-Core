<?php

namespace Addons\CatminBackupS3\Controllers\Admin;

use Addons\CatminBackupS3\Services\RemoteBackupService;
use Addons\CatminBackupS3\Services\RemoteBackupSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RemoteBackupController extends Controller
{
    public function __construct(
        private readonly RemoteBackupSettingsService $settingsService,
        private readonly RemoteBackupService $remoteBackupService,
    ) {
    }

    public function index(): View
    {
        $settings = $this->settingsService->all(false);
        $localBackups = $this->remoteBackupService->listLocalBackups();

        $remoteBackups = [];
        $connectionError = null;

        try {
            if ((bool) ($settings['enabled'] ?? false)) {
                $remoteBackups = $this->remoteBackupService->listRemoteBackups();
            }
        } catch (\Throwable $e) {
            $connectionError = $e->getMessage();
        }

        return view('catmin-backup-s3::admin.index', [
            'settings' => $settings,
            'localBackups' => $localBackups,
            'remoteBackups' => $remoteBackups,
            'connectionError' => $connectionError,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => ['required', 'in:s3,google,sftp,ftp'],
            'prefix' => ['nullable', 'string', 'max:191'],
            'retention_max' => ['nullable', 'integer', 'min:1', 'max:3650'],

            // S3
            'endpoint' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:120'],
            'bucket' => ['nullable', 'string', 'max:191'],
            'access_key' => ['nullable', 'string', 'max:191'],
            'secret_key' => ['nullable', 'string', 'max:2000'],
            'use_path_style_endpoint' => ['nullable', 'boolean'],

            // FTP/SFTP
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:2000'],
            'root' => ['nullable', 'string', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:600'],
            'passive' => ['nullable', 'boolean'],
            'ssl' => ['nullable', 'boolean'],
            'private_key' => ['nullable', 'string'],

            // Google
            'google_project_id' => ['nullable', 'string', 'max:191'],
            'google_bucket' => ['nullable', 'string', 'max:191'],
            'google_service_account_json' => ['nullable', 'string'],
        ]);

        $provider = (string) ($validated['provider'] ?? 's3');

        if ($provider === 's3') {
            $request->validate([
                'bucket' => ['required', 'string'],
                'access_key' => ['required_without:secret_key', 'string'],
            ]);
        }

        if ($provider === 'google') {
            $request->validate([
                'google_project_id' => ['required', 'string'],
                'google_bucket' => ['required', 'string'],
            ]);
        }

        if (in_array($provider, ['ftp', 'sftp'], true)) {
            $request->validate([
                'host' => ['required', 'string'],
                'username' => ['required', 'string'],
            ]);
        }

        $validated['enabled'] = $request->boolean('enabled');
        $validated['use_path_style_endpoint'] = $request->boolean('use_path_style_endpoint', true);
        $validated['passive'] = $request->boolean('passive', true);
        $validated['ssl'] = $request->boolean('ssl', false);

        $this->settingsService->save($validated);

        return redirect()->route('admin.backup.remote.index')->with('status', 'Configuration backup distant enregistrée.');
    }

    public function testConnection(): RedirectResponse
    {
        try {
            $ok = $this->remoteBackupService->testConnection();
        } catch (\Throwable $e) {
            return redirect()->route('admin.backup.remote.index')->with('error', 'Test de connexion échoué: ' . $e->getMessage());
        }

        return redirect()->route('admin.backup.remote.index')->with($ok ? 'status' : 'error', $ok ? 'Connexion distante OK.' : 'Connexion distante impossible.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_name' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->remoteBackupService->uploadLocalBackup((string) $validated['backup_name']);

        return redirect()->route('admin.backup.remote.index')->with(($result['ok'] ?? false) ? 'status' : 'error', (string) ($result['message'] ?? 'Operation terminee.'));
    }

    public function runRetention(): RedirectResponse
    {
        $result = $this->remoteBackupService->applyRetention();

        return redirect()->route('admin.backup.remote.index')->with(($result['ok'] ?? false) ? 'status' : 'error', (string) ($result['message'] ?? 'Retention terminee.'));
    }

    public function download(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'remote_path' => ['required', 'string', 'max:1024'],
        ]);

        $result = $this->remoteBackupService->downloadToLocal((string) $validated['remote_path']);

        return redirect()->route('admin.backup.remote.index')->with(($result['ok'] ?? false) ? 'status' : 'error', (string) ($result['message'] ?? 'Telechargement termine.'));
    }
}
