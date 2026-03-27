<?php

namespace Modules\Cron\Services;

use Illuminate\Support\Facades\DB;

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
        return [
            'cache.clear' => 'Vider le cache application',
            'views.clear' => 'Vider les vues compilées (Blade)',
            'queue.prune' => 'Nettoyer les jobs en file échoués',
        ];
    }

    /**
     * Execute a named task manually.
     */
    public static function runTask(string $taskKey): void
    {
        self::log($taskKey, 'started');
        try {
            match ($taskKey) {
                'cache.clear' => \Illuminate\Support\Facades\Artisan::call('cache:clear'),
                'views.clear' => \Illuminate\Support\Facades\Artisan::call('view:clear'),
                'queue.prune' => \Illuminate\Support\Facades\Artisan::call('queue:prune-failed', ['--hours' => 72]),
                default => throw new \InvalidArgumentException("Unknown task: {$taskKey}"),
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
}
