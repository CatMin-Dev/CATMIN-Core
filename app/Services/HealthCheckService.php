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
            self::checkModulesStatus(),
            self::checkQueueStatus(),
            self::checkApiStatus(),
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

    /**
     * @return array<string, mixed>
     */
    protected static function checkModulesStatus(): array
    {
        $issues = ModuleManager::stateIssues();
        $critical = collect($issues)->where('level', 'critical')->count();
        $enabled = ModuleManager::enabledCount();
        $total = ModuleManager::all()->count();

        return [
            'key' => 'modules_status',
            'label' => 'Etat des modules',
            'ok' => $critical === 0,
            'details' => sprintf('%d/%d modules actifs, %d probleme(s) critique(s).', $enabled, $total, $critical),
            'metrics' => [
                'enabled' => $enabled,
                'total' => $total,
                'critical_issues' => $critical,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkQueueStatus(): array
    {
        $connection = (string) config('queue.default', 'sync');
        $failedJobs = 0;

        try {
            $failedJobs = DB::table((string) config('queue.failed.table', 'failed_jobs'))->count();
        } catch (\Throwable) {
            // Ignore if table is missing in early setup.
        }

        $threshold = (int) config('catmin.health.failed_jobs_threshold', 50);
        $ok = $failedJobs <= $threshold;

        return [
            'key' => 'queue_status',
            'label' => 'Etat de la queue',
            'ok' => $ok,
            'details' => sprintf('Driver=%s, failed_jobs=%d (seuil=%d).', $connection, $failedJobs, $threshold),
            'metrics' => [
                'connection' => $connection,
                'failed_jobs' => $failedJobs,
                'threshold' => $threshold,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkApiStatus(): array
    {
        $internalPrefix = (string) config('catmin.api.prefix', 'api/internal');

        $errors = [];
        if ($internalPrefix === '') {
            $errors[] = 'prefix API interne vide';
        }

        return [
            'key' => 'api_status',
            'label' => 'Etat des APIs',
            'ok' => $errors === [],
            'details' => $errors === []
                ? sprintf('Interne=%s.', $internalPrefix)
                : implode('; ', $errors),
            'metrics' => [
                'internal_prefix' => $internalPrefix,
            ],
        ];
    }
}
