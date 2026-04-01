<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RecoveryEngineService
{
    private const LOG_FILE = 'logs/recovery-engine.jsonl';

    public function __construct(private readonly AutoUpdateService $autoUpdateService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'health' => HealthCheckService::run(),
            'update_status' => $this->autoUpdateService->status(),
            'active_update' => $this->autoUpdateService->activeUpdate(),
            'last_recovery' => $this->history(1)[0] ?? null,
            'release_tags' => $this->latestReleaseTags(5),
        ];
    }

    /**
     * @param array{maintenance_mode?: bool, rollback_code?: bool, restore_backup?: bool} $options
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $maintenanceMode = (bool) ($options['maintenance_mode'] ?? true);
        $rollbackCode = (bool) ($options['rollback_code'] ?? true);
        $restoreBackup = (bool) ($options['restore_backup'] ?? true);

        $steps = [];

        $this->log('recovery_started', [
            'options' => [
                'maintenance_mode' => $maintenanceMode,
                'rollback_code' => $rollbackCode,
                'restore_backup' => $restoreBackup,
            ],
        ]);

        try {
            if ($maintenanceMode) {
                Artisan::call('down', ['--render' => 'errors::503']);
                $steps[] = ['name' => 'maintenance_down', 'ok' => true, 'details' => 'Mode maintenance active.'];
            }

            if ($rollbackCode) {
                $codeResult = $this->rollbackCode();
                $steps[] = ['name' => 'rollback_code', 'ok' => (bool) ($codeResult['ok'] ?? false), 'details' => (string) ($codeResult['message'] ?? '')];

                if (($codeResult['ok'] ?? false) !== true) {
                    throw new \RuntimeException((string) ($codeResult['message'] ?? 'Rollback code echoue.'));
                }
            }

            if ($restoreBackup) {
                $backupResult = $this->autoUpdateService->rollbackLast();
                $steps[] = ['name' => 'restore_backup', 'ok' => (bool) ($backupResult['ok'] ?? false), 'details' => (string) ($backupResult['message'] ?? '')];

                if (($backupResult['ok'] ?? false) !== true) {
                    throw new \RuntimeException((string) ($backupResult['message'] ?? 'Restore backup echoue.'));
                }
            }

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            $steps[] = ['name' => 'cache_reset', 'ok' => true, 'details' => 'Caches Laravel nettoyes.'];

            $health = HealthCheckService::run();
            $steps[] = ['name' => 'post_health_check', 'ok' => (bool) ($health['ok'] ?? false), 'details' => 'Health check post-recovery execute.'];

            if ($maintenanceMode && ($health['ok'] ?? false) === true) {
                Artisan::call('up');
                $steps[] = ['name' => 'maintenance_up', 'ok' => true, 'details' => 'Mode maintenance desactive.'];
            }

            $ok = (bool) ($health['ok'] ?? false);

            $payload = [
                'ok' => $ok,
                'steps' => $steps,
                'health' => $health,
                'message' => $ok
                    ? 'Recovery terminee avec succes.'
                    : 'Recovery executee mais health check final non valide.',
            ];

            $this->log('recovery_finished', [
                'ok' => $ok,
                'steps' => $steps,
            ]);

            return $payload;
        } catch (\Throwable $e) {
            $steps[] = ['name' => 'recovery_error', 'ok' => false, 'details' => $e->getMessage()];

            $this->log('recovery_failed', [
                'error' => $e->getMessage(),
                'steps' => $steps,
            ]);

            return [
                'ok' => false,
                'steps' => $steps,
                'message' => 'Recovery interrompue: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rollbackCode(): array
    {
        $gitDir = base_path('.git');
        if (!File::exists($gitDir)) {
            return ['ok' => false, 'message' => 'Rollback code indisponible: repo git absent.'];
        }

        $statusProcess = new Process(['git', 'status', '--porcelain'], base_path());
        $statusProcess->run();
        if (!$statusProcess->isSuccessful()) {
            return ['ok' => false, 'message' => 'Impossible de verifier etat git.'];
        }

        if (trim((string) $statusProcess->getOutput()) !== '') {
            return ['ok' => false, 'message' => 'Working tree non propre, rollback code bloque.'];
        }

        $tag = $this->latestReleaseTag();
        if ($tag === null) {
            return ['ok' => false, 'message' => 'Aucun tag release/* disponible pour rollback code.'];
        }

        $checkout = new Process(['git', 'checkout', $tag, '--', '.'], base_path());
        $checkout->run();
        if (!$checkout->isSuccessful()) {
            return ['ok' => false, 'message' => 'Echec rollback code vers tag ' . $tag . '.'];
        }

        return ['ok' => true, 'message' => 'Code restore depuis tag ' . $tag . '.'];
    }

    /**
     * @return array<int, string>
     */
    public function latestReleaseTags(int $limit = 5): array
    {
        $process = new Process(['git', 'tag', '--list', 'release/*', '--sort=-creatordate'], base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $tags = preg_split('/\r\n|\r|\n/', trim((string) $process->getOutput())) ?: [];
        $tags = array_values(array_filter($tags, fn ($tag) => $tag !== ''));

        return array_slice($tags, 0, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $limit = 20): array
    {
        $path = storage_path(self::LOG_FILE);
        if (!File::exists($path)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) File::get($path)) ?: [];
        $entries = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    private function latestReleaseTag(): ?string
    {
        $tags = $this->latestReleaseTags(1);

        return $tags[0] ?? null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function log(string $event, array $payload = []): void
    {
        $path = storage_path(self::LOG_FILE);
        File::ensureDirectoryExists(dirname($path));

        $entry = [
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'payload' => $payload,
        ];

        File::append($path, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
