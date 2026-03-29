<?php

namespace Modules\Cron\Services;

use App\Services\ModuleConfigService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CronService
{
    /**
     * Write a cron execution entry to system_logs.
     */
    public static function log(string $task, string $status, ?string $output = null): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel' => 'cron',
                'level' => $status === 'error' ? 'error' : 'info',
                'event' => 'cron.task',
                'message' => "[{$task}] {$status}",
                'context' => $output ? json_encode(['output' => $output]) : null,
                'admin_username' => 'scheduler',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // table may not exist during initial setup
        }
    }

    /**
     * Available manual tasks (name => description).
     *
     * @return array<string, string>
     */
    public static function availableTasks(): array
    {
        $builtIn = [
            'cache.clear' => 'Vider le cache application',
            'views.clear' => 'Vider les vues compilées (Blade)',
            'queue.prune' => 'Nettoyer les jobs en file échoués',
            'logs.archive' => 'Archiver les logs systeme',
        ];

        foreach (self::customTasks() as $task) {
            $label = trim((string) ($task['label'] ?? ''));
            $key = (string) ($task['id'] ?? '');
            if ($key !== '' && $label !== '' && (bool) ($task['enabled'] ?? true)) {
                $builtIn[$key] = $label;
            }
        }

        return $builtIn;
    }

    /**
     * Execute a named task manually.
     */
    public static function runTask(string $taskKey): void
    {
        self::log($taskKey, 'started');
        try {
            $pruneHours = (int) ModuleConfigService::get('queue', 'prune_failed_hours', 72);

            match ($taskKey) {
                'cache.clear' => Artisan::call('cache:clear'),
                'views.clear' => Artisan::call('view:clear'),
                'queue.prune' => Artisan::call('queue:prune-failed', ['--hours' => max(1, $pruneHours)]),
                'logs.archive' => Artisan::call('catmin:logs:rotate'),
                default => self::runCustomTaskById($taskKey),
            };
            self::log($taskKey, 'done');
        } catch (\Throwable $e) {
            self::log($taskKey, 'error', $e->getMessage());
        }
    }

    /**
     * Recent cron log entries.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function recentLogs(int $limit = 50): \Illuminate\Support\Collection
    {
        try {
            return DB::table('system_logs')
                ->where('channel', 'cron')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function customTasks(): array
    {
        $raw = SettingService::get('module.cron.config.custom_tasks', '[]');

        if (is_array($raw)) {
            return array_values(array_filter($raw, 'is_array'));
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param array<string, mixed> $task
     */
    public static function addCustomTask(array $task): void
    {
        $tasks = self::customTasks();
        $id = 'custom.' . Str::slug((string) ($task['label'] ?? 'task')) . '.' . Str::lower(Str::random(6));

        $tasks[] = [
            'id' => $id,
            'label' => (string) ($task['label'] ?? 'Tache'),
            'description' => (string) ($task['description'] ?? ''),
            'command' => self::normalizeArtisanCommand((string) ($task['command'] ?? '')),
            'frequency' => (string) ($task['frequency'] ?? 'daily'),
            'scope' => (string) ($task['scope'] ?? 'site'),
            'module' => (string) ($task['module'] ?? ''),
            'enabled' => (bool) ($task['enabled'] ?? true),
            'created_at' => now()->toDateTimeString(),
        ];

        self::saveCustomTasks($tasks);
    }

    public static function removeCustomTask(string $taskId): void
    {
        $tasks = array_values(array_filter(self::customTasks(), fn (array $task) => (string) ($task['id'] ?? '') !== $taskId));
        self::saveCustomTasks($tasks);
    }

    /**
     * @return array<string, string>
     */
    public static function frequencyOptions(): array
    {
        return [
            'every_minute' => 'Chaque minute',
            'hourly' => 'Toutes les heures',
            'daily' => 'Chaque jour',
            'weekly' => 'Chaque semaine',
        ];
    }

    public static function runDueCustomTasks(): void
    {
        $runtimeRaw = SettingService::get('module.cron.config.custom_tasks_runtime', '{}');
        $runtime = is_array($runtimeRaw) ? $runtimeRaw : (json_decode((string) $runtimeRaw, true) ?: []);
        $now = now();

        foreach (self::customTasks() as $task) {
            if (!(bool) ($task['enabled'] ?? true)) {
                continue;
            }

            $taskId = (string) ($task['id'] ?? '');
            if ($taskId === '') {
                continue;
            }

            if (!self::isDue((string) ($task['frequency'] ?? 'daily'), $now)) {
                continue;
            }

            $bucket = self::runtimeBucket((string) ($task['frequency'] ?? 'daily'), $now);
            if (($runtime[$taskId] ?? null) === $bucket) {
                continue;
            }

            self::runTask($taskId);
            $runtime[$taskId] = $bucket;
        }

        SettingService::put(
            'module.cron.config.custom_tasks_runtime',
            $runtime,
            'json',
            'module.cron',
            'Runtime des taches cron personnalisées',
            false
        );
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     */
    private static function saveCustomTasks(array $tasks): void
    {
        SettingService::put(
            'module.cron.config.custom_tasks',
            $tasks,
            'json',
            'module.cron',
            'Taches cron personnalisées',
            false
        );
    }

    private static function runCustomTaskById(string $taskId): void
    {
        $task = collect(self::customTasks())->first(fn (array $row) => (string) ($row['id'] ?? '') === $taskId);
        if (!is_array($task)) {
            throw new \InvalidArgumentException("Unknown task: {$taskId}");
        }

        $command = self::normalizeArtisanCommand((string) ($task['command'] ?? ''));
        if ($command === '') {
            throw new \InvalidArgumentException("Empty command for task: {$taskId}");
        }

        [$signature, $arguments] = self::parseArtisanCommand($command);
        Artisan::call($signature, $arguments);
    }

    private static function normalizeArtisanCommand(string $command): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $command));
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function parseArtisanCommand(string $command): array
    {
        $parts = preg_split('/\s+/', trim($command)) ?: [];
        if ($parts === []) {
            return ['', []];
        }

        $signature = (string) array_shift($parts);
        $arguments = [];
        $position = 0;

        foreach ($parts as $part) {
            if (str_starts_with($part, '--')) {
                $option = substr($part, 2);
                if ($option === '') {
                    continue;
                }

                if (str_contains($option, '=')) {
                    [$name, $value] = explode('=', $option, 2);
                    $arguments['--' . $name] = $value;
                } else {
                    $arguments['--' . $option] = true;
                }

                continue;
            }

            $arguments[$position] = $part;
            $position++;
        }

        return [$signature, $arguments];
    }

    private static function isDue(string $frequency, \Illuminate\Support\Carbon $now): bool
    {
        return match ($frequency) {
            'every_minute' => true,
            'hourly' => (int) $now->format('i') === 0,
            'daily' => (int) $now->format('H') === 0 && (int) $now->format('i') === 0,
            'weekly' => (int) $now->format('N') === 1 && (int) $now->format('H') === 0 && (int) $now->format('i') === 0,
            default => false,
        };
    }

    private static function runtimeBucket(string $frequency, \Illuminate\Support\Carbon $now): string
    {
        return match ($frequency) {
            'every_minute' => $now->format('Y-m-d H:i'),
            'hourly' => $now->format('Y-m-d H'),
            'daily' => $now->format('Y-m-d'),
            'weekly' => $now->format('o-\\WW'),
            default => $now->toDateTimeString(),
        };
    }
}
