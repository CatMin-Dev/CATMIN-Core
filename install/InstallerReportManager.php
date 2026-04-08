<?php

declare(strict_types=1);

namespace Install;

final class InstallerReportManager
{
    public function generate(InstallerContext $context): string
    {
        $dir = CATMIN_STORAGE . '/install/reports';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $version = 'unknown';
        if (is_file(CATMIN_ROOT . '/version.json')) {
            $decoded = json_decode((string) file_get_contents(CATMIN_ROOT . '/version.json'), true);
            if (is_array($decoded) && is_string($decoded['version'] ?? null)) {
                $version = (string) $decoded['version'];
            }
        }

        $execution = is_array($context->data('execution')) ? $context->data('execution') : [];
        $profile = is_array($context->data('profile')) ? $context->data('profile') : [];
        $database = is_array($context->data('database')) ? $context->data('database') : [];
        $identity = is_array($context->data('identity')) ? $context->data('identity') : [];
        $system = is_array($context->data('system')) ? $context->data('system') : [];
        $modules = is_array($context->meta('planned_modules', [])) ? $context->meta('planned_modules', []) : [];

        $payload = [
            'generated_at' => date('c'),
            'state' => $context->state(),
            'completed_steps' => $context->completed(),
            'current_step' => $context->currentStep(),
            'core_version' => $version,
            'db_version' => (string) ($execution['db_version'] ?? '0.0.0-dev.0'),
            'environment' => (string) config('app.env', 'production'),
            'db_driver' => (string) ($database['driver'] ?? 'sqlite'),
            'profile' => (string) ($profile['profile'] ?? 'recommended'),
            'identity' => $identity,
            'security' => [
                'admin_path' => $context->data('security')['admin_path'] ?? 'admin',
                'ip_whitelist_enabled' => (bool) ($context->data('security')['ip_whitelist_enabled'] ?? false),
            ],
            'consent_tracking' => (bool) ($system['consent_tracking'] ?? false),
            'modules_activated' => array_values(array_map(
                static fn (array $m): string => (string) ($m['name'] ?? ''),
                array_filter($modules, static fn (mixed $m): bool => is_array($m) && !empty($m['enabled']))
            )),
            'warnings' => is_array($execution['warnings'] ?? null) ? $execution['warnings'] : [],
            'errors_non_blocking' => is_array($execution['errors'] ?? null) ? $execution['errors'] : [],
            'execution' => $execution,
        ];

        $name = 'install-report-' . date('Ymd-His') . '.json';
        $path = $dir . '/' . $name;

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        file_put_contents($dir . '/latest.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $path;
    }
}
