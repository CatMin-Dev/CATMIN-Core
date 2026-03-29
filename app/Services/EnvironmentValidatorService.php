<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class EnvironmentValidatorService
{
    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $items = [
            $this->checkPhpVersion(),
            $this->checkPhpExtensions(),
            $this->checkDirectoryPermissions(),
            $this->checkDatabaseConnection(),
            $this->checkEnvIntegrity(),
            $this->checkAppEnvDebug(),
            $this->checkAppKey(),
            $this->checkQueueActive(),
            $this->checkCronActive(),
        ];

        $summary = [
            'ok' => count(array_filter($items, fn (array $row): bool => $row['status'] === 'OK')),
            'warning' => count(array_filter($items, fn (array $row): bool => $row['status'] === 'WARNING')),
            'error' => count(array_filter($items, fn (array $row): bool => $row['status'] === 'ERROR')),
            'total' => count($items),
        ];

        $blocked = count(array_filter($items, fn (array $row): bool => (bool) ($row['critical'] ?? false))) > 0;

        return [
            'ok' => !$blocked,
            'blocked' => $blocked,
            'checked_at' => now()->toIso8601String(),
            'summary' => $summary,
            'items' => $items,
            'recommendations' => array_values(array_filter(array_map(function (array $item): ?array {
                if (($item['status'] ?? 'OK') === 'OK') {
                    return null;
                }

                return [
                    'severity' => (string) (($item['status'] ?? 'WARNING') === 'ERROR' ? 'critical' : 'warning'),
                    'title' => (string) ($item['label'] ?? 'Diagnostic'),
                    'message' => (string) ($item['recommendation'] ?? ''),
                    'url' => '',
                    'permission' => null,
                ];
            }, $items))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkPhpVersion(): array
    {
        $required = '8.1.0';
        $current = PHP_VERSION;
        $ok = version_compare($current, $required, '>=');

        return $this->item(
            key: 'php_version',
            label: 'PHP version',
            status: $ok ? 'OK' : 'ERROR',
            message: $ok ? "Version PHP compatible: {$current}." : "PHP {$required}+ requis, courant: {$current}.",
            recommendation: $ok ? '' : 'Mettre a jour PHP sur le serveur avant installation.',
            critical: !$ok
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkPhpExtensions(): array
    {
        $required = ['pdo_mysql', 'mbstring', 'json', 'fileinfo', 'openssl'];
        $missing = array_values(array_filter($required, static fn (string $ext): bool => !extension_loaded($ext)));
        $ok = $missing === [];

        return $this->item(
            key: 'php_extensions',
            label: 'Extensions PHP',
            status: $ok ? 'OK' : 'ERROR',
            message: $ok ? 'Extensions requises disponibles.' : 'Extensions manquantes: ' . implode(', ', $missing),
            recommendation: $ok ? '' : 'Installer/activer les extensions PHP manquantes et redemarrer PHP-FPM.',
            critical: !$ok,
            details: [
                'required' => $required,
                'missing' => $missing,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDirectoryPermissions(): array
    {
        $targets = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework/cache'),
            base_path('bootstrap/cache'),
        ];

        $failed = [];
        foreach ($targets as $target) {
            if (!is_dir($target) || !is_writable($target)) {
                $failed[] = $target;
            }
        }

        $ok = $failed === [];

        return $this->item(
            key: 'permissions',
            label: 'Permissions storage/cache',
            status: $ok ? 'OK' : 'ERROR',
            message: $ok ? 'Permissions dossiers conformes.' : 'Dossiers non accessibles en ecriture: ' . implode(', ', $failed),
            recommendation: $ok ? '' : 'Corriger owner/group et chmod sur storage et bootstrap/cache.',
            critical: !$ok,
            details: [
                'checked' => $targets,
                'failed' => $failed,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->item(
                key: 'database',
                label: 'DB connectee',
                status: 'OK',
                message: 'Connexion base de donnees OK.',
                recommendation: '',
                critical: false
            );
        } catch (\Throwable $throwable) {
            return $this->item(
                key: 'database',
                label: 'DB connectee',
                status: 'ERROR',
                message: 'Connexion DB impossible: ' . $throwable->getMessage(),
                recommendation: 'Verifier DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.',
                critical: true
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkEnvIntegrity(): array
    {
        $envPath = base_path('.env');
        if (!is_file($envPath)) {
            return $this->item(
                key: 'env_file',
                label: '.env valide',
                status: 'ERROR',
                message: 'Fichier .env absent.',
                recommendation: 'Copier .env.example vers .env et renseigner les valeurs obligatoires.',
                critical: true
            );
        }

        $content = (string) @file_get_contents($envPath);
        $required = ['APP_ENV', 'APP_DEBUG', 'APP_KEY'];
        $missing = [];
        foreach ($required as $name) {
            if (!preg_match('/^' . preg_quote($name, '/') . '=.*/m', $content)) {
                $missing[] = $name;
            }
        }

        $ok = $missing === [];

        return $this->item(
            key: 'env_file',
            label: '.env valide',
            status: $ok ? 'OK' : 'ERROR',
            message: $ok ? '.env present et lisible.' : 'Variables absentes dans .env: ' . implode(', ', $missing),
            recommendation: $ok ? '' : 'Completer .env avec APP_ENV, APP_DEBUG et APP_KEY.',
            critical: !$ok,
            details: [
                'missing' => $missing,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkAppEnvDebug(): array
    {
        $env = (string) config('app.env', 'production');
        $debug = (bool) config('app.debug', false);

        if ($env === 'production' && $debug) {
            return $this->item(
                key: 'app_env_debug',
                label: 'APP_ENV / APP_DEBUG',
                status: 'ERROR',
                message: 'APP_DEBUG actif en production.',
                recommendation: 'Passer APP_DEBUG=false en production.',
                critical: true,
                details: ['env' => $env, 'debug' => $debug]
            );
        }

        return $this->item(
            key: 'app_env_debug',
            label: 'APP_ENV / APP_DEBUG',
            status: 'OK',
            message: "ENV={$env}, DEBUG=" . ($debug ? 'true' : 'false') . '.',
            recommendation: '',
            critical: false,
            details: ['env' => $env, 'debug' => $debug]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkAppKey(): array
    {
        $key = trim((string) config('app.key', ''));
        $ok = $key !== '' && $key !== 'base64:' && str_starts_with($key, 'base64:');

        return $this->item(
            key: 'app_key',
            label: 'Cle app generee',
            status: $ok ? 'OK' : 'ERROR',
            message: $ok ? 'APP_KEY valide.' : 'APP_KEY absente ou invalide.',
            recommendation: $ok ? '' : 'Executer php artisan key:generate puis redemarrer l application.',
            critical: !$ok
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkQueueActive(): array
    {
        $driver = (string) config('queue.default', 'sync');
        $failedJobs = 0;

        try {
            if (DB::getSchemaBuilder()->hasTable((string) config('queue.failed.table', 'failed_jobs'))) {
                $failedJobs = DB::table((string) config('queue.failed.table', 'failed_jobs'))->count();
            }
        } catch (\Throwable) {
            $failedJobs = 0;
        }

        if ($driver === 'sync') {
            return $this->item(
                key: 'queue_active',
                label: 'Queue active',
                status: 'WARNING',
                message: 'Queue driver=sync (worker dedie non actif).',
                recommendation: 'Configurer queue redis/database et lancer un worker supervise.',
                critical: false,
                details: ['driver' => $driver, 'failed_jobs' => $failedJobs]
            );
        }

        return $this->item(
            key: 'queue_active',
            label: 'Queue active',
            status: $failedJobs > 20 ? 'WARNING' : 'OK',
            message: "Queue driver={$driver}, failed_jobs={$failedJobs}.",
            recommendation: $failedJobs > 20 ? 'Traiter les failed_jobs et verifier les workers.' : '',
            critical: false,
            details: ['driver' => $driver, 'failed_jobs' => $failedJobs]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkCronActive(): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('system_logs')) {
                return $this->item(
                    key: 'cron_active',
                    label: 'Cron actif',
                    status: 'WARNING',
                    message: 'Table system_logs indisponible pour verifier cron.',
                    recommendation: 'Verifier la migration logger et la tache schedule:run.',
                    critical: false
                );
            }

            $lastRun = DB::table('system_logs')
                ->where('channel', 'cron')
                ->where('event', 'cron.task')
                ->max('created_at');

            if ($lastRun === null) {
                return $this->item(
                    key: 'cron_active',
                    label: 'Cron actif',
                    status: 'WARNING',
                    message: 'Aucune execution cron detectee.',
                    recommendation: 'Installer le cron Linux: * * * * * php artisan schedule:run',
                    critical: false
                );
            }

            $stale = now()->diffInMinutes((string) $lastRun) > 15;

            return $this->item(
                key: 'cron_active',
                label: 'Cron actif',
                status: $stale ? 'WARNING' : 'OK',
                message: $stale ? 'Derniere execution cron trop ancienne.' : 'Cron actif recemment.',
                recommendation: $stale ? 'Verifier le daemon cron et le schedule:run chaque minute.' : '',
                critical: false,
                details: ['last_run' => (string) $lastRun]
            );
        } catch (\Throwable $throwable) {
            return $this->item(
                key: 'cron_active',
                label: 'Cron actif',
                status: 'WARNING',
                message: 'Verification cron indisponible: ' . $throwable->getMessage(),
                recommendation: 'Verifier les logs cron et l acces DB.',
                critical: false
            );
        }
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function item(string $key, string $label, string $status, string $message, string $recommendation, bool $critical, array $details = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'message' => $message,
            'recommendation' => $recommendation,
            'critical' => $critical,
            'details' => $details,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
