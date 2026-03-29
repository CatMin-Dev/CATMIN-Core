<?php

namespace Modules\Queue\Services;

use App\Services\ModuleConfigService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueAdminService
{
    /**
     * @return array{pending: int, failed: int}
     */
    public function counters(): array
    {
        return [
            'pending' => $this->safeCount('jobs'),
            'failed' => $this->safeCount('failed_jobs'),
        ];
    }

    /**
     * @param array{queue?: string, q?: string} $filters
     */
    public function failedJobs(array $filters, int $perPage = 20, string $pageName = 'failed_page'): LengthAwarePaginator
    {
        $query = DB::table('failed_jobs')->orderByDesc('failed_at');

        $queue = trim((string) ($filters['queue'] ?? ''));
        if ($queue !== '') {
            $query->where('queue', $queue);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('id', 'like', '%' . $search . '%')
                    ->orWhere('exception', 'like', '%' . $search . '%')
                    ->orWhere('payload', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query->paginate(max(5, $perPage), ['*'], $pageName)->withQueryString();

        return $paginator->through(function ($row): array {
            $payload = $this->decodePayload((string) ($row->payload ?? ''));

            return [
                'id' => (int) $row->id,
                'uuid' => (string) ($row->uuid ?? ''),
                'queue' => (string) ($row->queue ?? 'default'),
                'connection' => (string) ($row->connection ?? ''),
                'status' => 'failed',
                'attempts' => (int) ($payload['attempts'] ?? $payload['tries'] ?? 0),
                'class' => $this->jobClassFromPayload($payload),
                'error_excerpt' => $this->exceptionExcerpt((string) ($row->exception ?? '')),
                'payload_preview' => $this->payloadPreview($payload),
                'failed_at' => $row->failed_at,
            ];
        });
    }

    /**
     * @param array{queue?: string, q?: string} $filters
     */
    public function pendingJobs(array $filters, int $perPage = 20, string $pageName = 'pending_page'): LengthAwarePaginator
    {
        $query = DB::table('jobs')->orderByDesc('id');

        $queue = trim((string) ($filters['queue'] ?? ''));
        if ($queue !== '') {
            $query->where('queue', $queue);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('id', 'like', '%' . $search . '%')
                    ->orWhere('payload', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query->paginate(max(5, $perPage), ['*'], $pageName)->withQueryString();

        return $paginator->through(function ($row): array {
            $payload = $this->decodePayload((string) ($row->payload ?? ''));

            return [
                'id' => (int) $row->id,
                'queue' => (string) ($row->queue ?? 'default'),
                'status' => ((int) ($row->reserved_at ?? 0)) > 0 ? 'running' : 'pending',
                'attempts' => (int) ($row->attempts ?? 0),
                'class' => $this->jobClassFromPayload($payload),
                'payload_preview' => $this->payloadPreview($payload),
                'created_at' => $this->fromUnixTimestamp($row->created_at ?? null),
                'available_at' => $this->fromUnixTimestamp($row->available_at ?? null),
                'reserved_at' => $this->fromUnixTimestamp($row->reserved_at ?? null),
            ];
        });
    }

    /**
     * @return array<int, string>
     */
    public function availableQueues(): array
    {
        try {
            $jobsQueues = DB::table('jobs')->select('queue')->whereNotNull('queue');
            $failedQueues = DB::table('failed_jobs')->select('queue')->whereNotNull('queue');

            return $jobsQueues
                ->union($failedQueues)
                ->pluck('queue')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, int>
     */
    public function allFailedIds(): array
    {
        try {
            return DB::table('failed_jobs')
                ->orderByDesc('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function failedDetail(int $id): ?array
    {
        $row = DB::table('failed_jobs')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        $payload = $this->decodePayload((string) ($row->payload ?? ''));

        return [
            'source' => 'failed',
            'id' => (int) $row->id,
            'uuid' => (string) ($row->uuid ?? ''),
            'queue' => (string) ($row->queue ?? 'default'),
            'connection' => (string) ($row->connection ?? ''),
            'status' => 'failed',
            'attempts' => (int) ($payload['attempts'] ?? $payload['tries'] ?? 0),
            'class' => $this->jobClassFromPayload($payload),
            'failed_at' => $row->failed_at,
            'created_at' => null,
            'available_at' => null,
            'reserved_at' => null,
            'payload' => $this->sanitizePayload($payload),
            'exception_excerpt' => $this->exceptionExcerpt((string) ($row->exception ?? '')),
            'exception' => (string) ($row->exception ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pendingDetail(int $id): ?array
    {
        $row = DB::table('jobs')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        $payload = $this->decodePayload((string) ($row->payload ?? ''));

        return [
            'source' => 'pending',
            'id' => (int) $row->id,
            'uuid' => '',
            'queue' => (string) ($row->queue ?? 'default'),
            'connection' => config('queue.default', 'database'),
            'status' => ((int) ($row->reserved_at ?? 0)) > 0 ? 'running' : 'pending',
            'attempts' => (int) ($row->attempts ?? 0),
            'class' => $this->jobClassFromPayload($payload),
            'failed_at' => null,
            'created_at' => $this->fromUnixTimestamp($row->created_at ?? null),
            'available_at' => $this->fromUnixTimestamp($row->available_at ?? null),
            'reserved_at' => $this->fromUnixTimestamp($row->reserved_at ?? null),
            'payload' => $this->sanitizePayload($payload),
            'exception_excerpt' => null,
            'exception' => '',
        ];
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function retryFailedIds(array $ids): int
    {
        $normalized = $this->normalizeIds($ids);
        if ($normalized === []) {
            return 0;
        }

        $exitCode = Artisan::call('queue:retry', ['id' => array_map('strval', $normalized)]);
        if ($exitCode !== 0) {
            throw new \RuntimeException('queue:retry returned non-zero exit code');
        }

        return count($normalized);
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function deleteFailedIds(array $ids): int
    {
        $normalized = $this->normalizeIds($ids);
        if ($normalized === []) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->whereIn('id', $normalized)->delete();
    }

    public function clearFailed(): void
    {
        DB::table('failed_jobs')->truncate();
    }

    public function failedJobsLimit(): int
    {
        return max(5, (int) ModuleConfigService::get('queue', 'failed_jobs_limit', 20));
    }

    private function safeCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $payload): array
    {
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function jobClassFromPayload(array $payload): string
    {
        $class = (string) ($payload['displayName'] ?? $payload['job'] ?? 'UnknownJob');

        return Str::limit($class, 140, '...');
    }

    private function exceptionExcerpt(string $exception): string
    {
        $firstLine = trim((string) Str::of($exception)->before("\n"));
        if ($firstLine === '') {
            $firstLine = 'Exception non detaillee';
        }

        return Str::limit($firstLine, 160, '...');
    }

    private function payloadPreview(array $payload): string
    {
        $preview = [
            'displayName' => $payload['displayName'] ?? null,
            'job' => $payload['job'] ?? null,
            'attempts' => $payload['attempts'] ?? null,
            'tries' => $payload['tries'] ?? null,
            'timeout' => $payload['timeout'] ?? null,
            'uuid' => $payload['uuid'] ?? null,
        ];

        $preview = array_filter($preview, fn ($value) => $value !== null && $value !== '');

        return $preview === [] ? 'Payload non detaille' : Str::limit(json_encode($preview, JSON_UNESCAPED_SLASHES), 180, '...');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveNeedles = ['password', 'token', 'secret', 'authorization', 'cookie', 'api_key', 'apikey'];
        $result = [];

        foreach ($payload as $key => $value) {
            $keyString = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveNeedles as $needle) {
                if (str_contains($keyString, $needle)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $result[(string) $key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $result[(string) $key] = $this->sanitizePayload($value);
                continue;
            }

            if (is_string($value)) {
                $result[(string) $key] = Str::limit($value, 1200, '...');
                continue;
            }

            $result[(string) $key] = $value;
        }

        return $result;
    }

    private function fromUnixTimestamp(mixed $value): ?string
    {
        $timestamp = (int) ($value ?? 0);
        if ($timestamp <= 0) {
            return null;
        }

        return now()->setTimestamp($timestamp)->toDateTimeString();
    }

    /**
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
