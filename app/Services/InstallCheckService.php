<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InstallCheckService
{
    /**
     * @return array<string, mixed>
     */
    public static function run(): array
    {
        $checks = [
            'php_version' => self::checkPhpVersion(),
            'php_extensions' => self::checkPhpExtensions(),
            'database' => self::checkDatabaseConnection(),
            'directories' => self::checkDirectories(),
            'environment' => self::checkEnvironmentVariables(),
            'security_guardrails' => app(SecurityHardeningService::class)->installCheck(),
        ];

        $errors = [];
        $warnings = [];

        foreach ($checks as $key => $result) {
            if (($result['ok'] ?? false) !== true) {
                $errors[$key] = $result;
                continue;
            }

            $status = (string) ($result['status'] ?? 'ok');
            if ($status === 'warning') {
                $warnings[$key] = $result;
            }
        }

        return [
            'ok' => empty($errors),
            'checks' => $checks,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function checkPhpVersion(): array
    {
        $required = '8.2.0';
        $current = PHP_VERSION;
        $ok = version_compare($current, $required, '>=');

        return [
            'ok' => $ok,
            'required' => $required,
            'current' => $current,
            'message' => $ok
                ? 'Version PHP compatible.'
                : "PHP {$required}+ requis, version actuelle: {$current}.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function checkPhpExtensions(): array
    {
        $required = ['json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'ctype', 'fileinfo'];
        $missing = [];

        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        $ok = empty($missing);

        return [
            'ok' => $ok,
            'required' => $required,
            'missing' => $missing,
            'message' => $ok
                ? 'Extensions PHP ok.'
                : 'Extensions manquantes: ' . implode(', ', $missing),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'ok' => true,
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'message' => 'Connexion base de données OK.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'connection' => config('database.default'),
                'message' => 'Connexion base de données impossible: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function checkDirectories(): array
    {
        $targets = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];

        $failed = [];

        foreach ($targets as $target) {
            if (!is_dir($target) || !is_writable($target)) {
                $failed[] = $target;
            }
        }

        $ok = empty($failed);

        return [
            'ok' => $ok,
            'checked' => $targets,
            'failed' => $failed,
            'message' => $ok
                ? 'Permissions dossiers OK.'
                : 'Dossiers non accessibles en ecriture: ' . implode(', ', $failed),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function checkEnvironmentVariables(): array
    {
        $required = [
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
        ];

        $missing = [];

        foreach ($required as $name) {
            $value = env($name);
            if ($value === null || $value === '') {
                $missing[] = $name;
            }
        }

        $ok = empty($missing);

        return [
            'ok' => $ok,
            'required' => $required,
            'missing' => $missing,
            'message' => $ok
                ? 'Variables d\'environnement OK.'
                : 'Variables manquantes: ' . implode(', ', $missing),
        ];
    }
}
