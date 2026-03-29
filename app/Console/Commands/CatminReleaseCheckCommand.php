<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SecurityHardeningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Logger\Models\SystemAlert;

class CatminReleaseCheckCommand extends Command
{
    protected $signature = 'catmin:release:check {--detailed : afficher details} {--json : sortie JSON}';

    protected $description = 'Verifier readiness V2 stable release (P0 bloquants)';

    private array $checks = [];

    public function handle(): int
    {
        $this->info('=== CATMIN Release Readiness Check (P0 bloquants) ===');
        $this->newLine();

        $this->checkSecurityP0();
        $this->checkRbacCoverage();
        $this->checkWebhooksSecurity();
        $this->checkTestsStability();
        $this->checkLogsMonitoring();
        $this->checkCriticalIncidents();

        $summary = $this->summarizeChecks();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->renderSummary($summary);
        }

        $blockerCount = collect($this->checks)->where('status', 'critical')->count();

        return $blockerCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function checkSecurityP0(): void
    {
        $checks = app(SecurityHardeningService::class)->collectGuardrails();
        $summary = app(SecurityHardeningService::class)->summarize($checks);

        $this->checks[] = [
            'name' => 'Security P0 Guardrails',
            'status' => (int) ($summary['critical'] ?? 0) === 0 ? 'ok' : 'critical',
            'detail' => sprintf(
                'critical=%d, warning=%d',
                (int) ($summary['critical'] ?? 0),
                (int) ($summary['warning'] ?? 0)
            ),
            'context' => $checks,
        ];
    }

    private function checkRbacCoverage(): void
    {
        try {
            $sensitiveRoutes = [
                'admin.modules.enable',
                'admin.modules.disable',
                'admin.modules.migrate',
                'admin.users.index',
                'admin.roles.index',
                'admin.settings.index',
            ];

            $missing = [];
            foreach ($sensitiveRoutes as $route) {
                if (!$this->isRouteProtectedByRbac($route)) {
                    $missing[] = $route;
                }
            }

            $this->checks[] = [
                'name' => 'RBAC Coverage (routes sensibles)',
                'status' => empty($missing) ? 'ok' : 'critical',
                'detail' => empty($missing)
                    ? sprintf('OK: %d routes protegees', count($sensitiveRoutes))
                    : sprintf('CRITICAL: %d routes sans permission check', count($missing)),
                'context' => $missing,
            ];
        } catch (\Throwable $e) {
            $this->checks[] = [
                'name' => 'RBAC Coverage',
                'status' => 'warning',
                'detail' => 'Check non executable (pas acces routes)',
                'context' => [],
            ];
        }
    }

    private function checkWebhooksSecurity(): void
    {
        $webhook_secret = (string) config('catmin.webhooks.incoming_secret', '');
        $has_token = (bool) config('catmin.webhooks.incoming_token', '');

        $ok = !empty($webhook_secret) || $has_token;

        $this->checks[] = [
            'name' => 'Webhooks Security (token ou secret)',
            'status' => $ok ? 'ok' : 'warning',
            'detail' => $ok
                ? 'Token ou secret present'
                : 'Webhooks non securises (recommandation)',
            'context' => [],
        ];
    }

    private function checkTestsStability(): void
    {
        try {
            $testPath = base_path('phpunit.xml');
            if (!file_exists($testPath)) {
                throw new \Exception('phpunit.xml absent');
            }

            $this->checks[] = [
                'name' => 'Tests Stability (execution)',
                'status' => 'warning',
                'detail' => 'Verifiez manually: php artisan test',
                'context' => [],
            ];
        } catch (\Throwable $e) {
            $this->checks[] = [
                'name' => 'Tests Stability',
                'status' => 'warning',
                'detail' => 'Pas de suite tests detectable',
                'context' => [],
            ];
        }
    }

    private function checkLogsMonitoring(): void
    {
        try {
            $logCount = DB::table('system_logs', 'l')
                ->where('level', 'error')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            $status = $logCount > 20 ? 'warning' : 'ok';

            $this->checks[] = [
                'name' => 'Logs & Monitoring (erreurs derniere heure)',
                'status' => $status,
                'detail' => sprintf('%d erreurs detectees (seuil warning: 20)', $logCount),
                'context' => [],
            ];
        } catch (\Throwable) {
            $this->checks[] = [
                'name' => 'Logs & Monitoring',
                'status' => 'warning',
                'detail' => 'Table system_logs absente',
                'context' => [],
            ];
        }
    }

    private function checkCriticalIncidents(): void
    {
        try {
            $criticalIncidents = 0;
            if (DB::getSchemaBuilder()->hasTable('monitoring_incidents')) {
                $criticalIncidents = DB::table('monitoring_incidents')
                    ->where('status', 'critical')
                    ->count();
            }

            $status = $criticalIncidents > 0 ? 'warning' : 'ok';

            $this->checks[] = [
                'name' => 'Monitoring Incidents (critique)',
                'status' => $status,
                'detail' => sprintf('%d incident(s) critique(s) ouvert(s)', $criticalIncidents),
                'context' => [],
            ];
        } catch (\Throwable) {
            $this->checks[] = [
                'name' => 'Monitoring Incidents',
                'status' => 'warning',
                'detail' => 'Monitoring module pas accessible',
                'context' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeChecks(): array
    {
        $critical = collect($this->checks)->where('status', 'critical')->count();
        $warnings = collect($this->checks)->where('status', 'warning')->count();
        $ok = collect($this->checks)->where('status', 'ok')->count();

        return [
            'summary' => [
                'total' => count($this->checks),
                'ok' => $ok,
                'warning' => $warnings,
                'critical' => $critical,
            ],
            'checks' => $this->checks,
            'recommendation' => $critical === 0 && $warnings === 0
                ? 'RELEASE READY (P0 bloquants fermes)'
                : ($critical === 0 ? 'RELEASE WITH CAUTION (P1+ warnings)' : 'NO-GO (P0 bloquants ouverts)'),
        ];
    }

    private function renderSummary(array $summary): void
    {
        $rec = (string) ($summary['recommendation'] ?? '');

        foreach ($this->checks as $check) {
            $name = (string) ($check['name'] ?? '?');
            $status = (string) ($check['status'] ?? 'unknown');
            $detail = (string) ($check['detail'] ?? '');

            $icon = match ($status) {
                'ok' => '✓',
                'warning' => '⚠',
                'critical' => '✗',
                default => '?',
            };

            $this->line(sprintf(
                '%s [%-10s] %s — %s',
                $icon,
                strtoupper($status),
                $name,
                $detail
            ));

            if ((bool) $this->option('detailed') && !empty($check['context'])) {
                $context = (array) ($check['context'] ?? []);
                foreach ($context as $item) {
                    if (is_string($item)) {
                        $this->line('      - ' . $item);
                    }
                }
            }
        }

        $this->newLine();
        $summary_str = sprintf(
            'Total: %d | OK: %d | Warning: %d | Critical: %d',
            (int) ($summary['summary']['total'] ?? 0),
            (int) ($summary['summary']['ok'] ?? 0),
            (int) ($summary['summary']['warning'] ?? 0),
            (int) ($summary['summary']['critical'] ?? 0)
        );
        $this->info($summary_str);

        $this->newLine();
        if ($rec === 'RELEASE READY (P0 bloquants fermes)') {
            $this->info('✓ ' . $rec);
        } elseif ($rec === 'RELEASE WITH CAUTION (P1+ warnings)') {
            $this->warn('⚠ ' . $rec);
        } else {
            $this->error('✗ ' . $rec);
        }
    }

    private function isRouteProtectedByRbac(string $routeName): bool
    {
        try {
            $routes = app('router')->getRoutes();
            $route = collect($routes)->first(fn ($r) => $r->getName() === $routeName);

            if (!$route) {
                return false;
            }

            $middleware = (array) $route->middleware();

            return collect($middleware)->contains(fn ($m) => str_contains($m, 'permission'));
        } catch (\Throwable) {
            return false;
        }
    }
}
