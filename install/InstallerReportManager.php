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

        $payload = [
            'generated_at' => date('c'),
            'completed_steps' => $context->completed(),
            'current_step' => $context->currentStep(),
            'profile' => $context->data('profile'),
            'identity' => $context->data('identity'),
            'security' => [
                'admin_path' => $context->data('security')['admin_path'] ?? 'admin',
                'ip_whitelist_enabled' => (bool) ($context->data('security')['ip_whitelist_enabled'] ?? false),
            ],
            'execution' => $context->data('execution'),
        ];

        $name = 'install-report-' . date('Ymd-His') . '.json';
        $path = $dir . '/' . $name;

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        file_put_contents($dir . '/latest.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $path;
    }
}
