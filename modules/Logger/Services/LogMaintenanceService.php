<?php

namespace Modules\Logger\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LogMaintenanceService
{
    /**
     * @param array{level?:string,channel?:string,from?:string,to?:string} $filters
     */
    public function purge(array $filters = []): int
    {
        $query = DB::table('system_logs');

        $this->applyFilters($query, $filters);

        return (int) $query->delete();
    }

    public function rotateDaily(int $retentionDays = 14, int $archiveRetentionDays = 90): array
    {
        $cutoff = now()->subDays($retentionDays);

        $toArchive = DB::table('system_logs')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->limit(5000)
            ->get();

        $archived = 0;

        foreach ($toArchive as $row) {
            DB::table('system_logs_archive')->insert([
                'archive_date' => now()->toDateString(),
                'channel' => (string) ($row->channel ?? ''),
                'level' => (string) ($row->level ?? ''),
                'event' => (string) ($row->event ?? ''),
                'message' => (string) ($row->message ?? ''),
                'context' => json_encode([
                    'original' => $row->context,
                    'compressed' => base64_encode(gzencode((string) ($row->context ?? '{}'))),
                    'compression' => 'gz+base64',
                ]),
                'admin_username' => (string) ($row->admin_username ?? ''),
                'method' => (string) ($row->method ?? ''),
                'url' => mb_substr((string) ($row->url ?? ''), 0, 255),
                'ip_address' => (string) ($row->ip_address ?? ''),
                'status_code' => isset($row->status_code) ? (int) $row->status_code : null,
                'log_count' => 1,
                'created_at' => $row->created_at,
            ]);

            $archived++;
        }

        if ($archived > 0) {
            DB::table('system_logs')
                ->where('created_at', '<=', $cutoff)
                ->limit($archived)
                ->delete();
        }

        $purgedArchive = DB::table('system_logs_archive')
            ->where('created_at', '<=', now()->subDays($archiveRetentionDays))
            ->delete();

        return [
            'archived' => $archived,
            'purged_archive' => (int) $purgedArchive,
            'retention_days' => $retentionDays,
            'archive_retention_days' => $archiveRetentionDays,
        ];
    }

    /**
     * @param array{level?:string,channel?:string,from?:string,to?:string} $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $level = trim((string) ($filters['level'] ?? ''));
        $channel = trim((string) ($filters['channel'] ?? ''));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        if ($level !== '') {
            $query->where('level', $level);
        }

        if ($channel !== '') {
            $query->where('channel', $channel);
        }

        if ($from !== '') {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to !== '') {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }
    }
}
