<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HealthCheckService
{
    /**
     * @return array{ok: bool, checks: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public static function run(): array
    {
        $checks = [
            self::checkDatabase(),
            self::checkStorage(),
            self::checkUploads(),
            self::checkCriticalModules(),
            self::checkMinimumConfig(),
        ];

        $summary = [
            'total' => count($checks),
            'ok' => collect($checks)->where('ok', true)->count(),
            'nok' => collect($checks)->where('ok', false)->count(),
        ];

        return [
            'ok' => $summary['nok'] === 0,
            'checks' => $checks,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'key' => 'database',
                'label' => 'Connexion base de donnees',
                'ok' => true,
                'details' => 'Connexion OK.',
            ];
        } catch (\Throwable $e) {
            return [
                'key' => 'database',
                'label' => 'Connexion base de donnees',
                'ok' => false,
                'details' => 'Connexion echouee: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkStorage(): array
    {
        $paths = [
            storage_path(),
            storage_path('framework/cache'),
            storage_path('logs'),
        ];

        $bad = [];
        foreach ($paths as $path) {
            if (!File::exists($path) || !is_writable($path)) {
                $bad[] = $path;
            }
        }

        return [
            'key' => 'storage',
            'label' => 'Dossiers storage accessibles',
            'ok' => $bad === [],
            'details' => $bad === [] ? 'Permissions storage OK.' : 'Non accessibles: ' . implode(', ', $bad),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkUploads(): array
    {
        $uploadsDir = storage_path('app/public/media');
        File::ensureDirectoryExists($uploadsDir);

        $ok = File::exists($uploadsDir) && is_readable($uploadsDir) && is_writable($uploadsDir);

        return [
            'key' => 'uploads',
            'label' => 'Zone uploads/media accessible',
            'ok' => $ok,
            'details' => $ok ? 'Uploads media lisibles/ecrivable.' : 'Uploads media non accessibles.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkCriticalModules(): array
    {
        $critical = ['core', 'users', 'settings'];
        $missing = [];

        foreach ($critical as $slug) {
            if (!ModuleManager::exists($slug)) {
                $missing[] = $slug;
            }
        }

        return [
            'key' => 'critical_modules',
            'label' => 'Modules critiques presents',
            'ok' => $missing === [],
            'details' => $missing === [] ? 'Modules critiques presents.' : 'Modules manquants: ' . implode(', ', $missing),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkMinimumConfig(): array
    {
        $errors = [];

        $appUrl = (string) config('app.url', '');
        if ($appUrl === '' || filter_var($appUrl, FILTER_VALIDATE_URL) === false) {
            $errors[] = 'APP_URL invalide';
        }

        $adminPath = trim((string) config('catmin.admin.path', ''));
        if ($adminPath === '') {
            $errors[] = 'catmin.admin.path vide';
        }

        $adminUser = trim((string) config('catmin.admin.username', ''));
        $adminPass = trim((string) config('catmin.admin.password', ''));
        if ($adminUser === '' || $adminPass === '') {
            $errors[] = 'credentials admin manquants';
        }

        return [
            'key' => 'config',
            'label' => 'Configuration minimale valide',
            'ok' => $errors === [],
            'details' => $errors === [] ? 'Configuration minimale OK.' : implode('; ', $errors),
        ];
    }
}
