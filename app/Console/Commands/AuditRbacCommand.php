<?php

namespace App\Console\Commands;

use App\Services\RbacAuditService;
use Illuminate\Console\Command;

class AuditRbacCommand extends Command
{
    protected $signature = 'catmin:audit-rbac
        {--json : sortie JSON complete dans la console}
        {--save : sauvegarde un rapport JSON+Markdown dans storage/app/reports}';

    protected $description = 'Audit de couverture RBAC des routes admin sensibles';

    public function handle(): int
    {
        $report = RbacAuditService::generate();
        $summary = (array) ($report['summary'] ?? []);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return ((int) ($summary['sensitive_routes_unprotected'] ?? 0) === 0 && (int) ($summary['inconsistent_routes'] ?? 0) === 0)
                ? self::SUCCESS
                : self::FAILURE;
        }

        $this->info('CATMIN RBAC Audit');
        $this->line('- Admin routes total: ' . (int) ($summary['admin_routes_total'] ?? 0));
        $this->line('- Sensitive routes total: ' . (int) ($summary['sensitive_routes_total'] ?? 0));
        $this->line('- Protected sensitive routes: ' . (int) ($summary['sensitive_routes_protected'] ?? 0));
        $this->line('- Unprotected sensitive routes: ' . (int) ($summary['sensitive_routes_unprotected'] ?? 0));
        $this->line('- Coverage: ' . (float) ($summary['sensitive_coverage_percent'] ?? 0) . '%');
        $this->line('- Inconsistent permissions: ' . (int) ($summary['inconsistent_routes'] ?? 0));

        $unprotectedRows = collect((array) ($report['sensitive_unprotected'] ?? []))
            ->map(fn (array $route) => [
                (string) ($route['name'] ?? 'unnamed'),
                (string) ($route['methods'] ?? ''),
                '/' . ltrim((string) ($route['uri'] ?? ''), '/'),
            ])
            ->values()
            ->toArray();

        if ($unprotectedRows !== []) {
            $this->line('');
            $this->warn('Sensitive routes without catmin.permission:');
            $this->table(['Route', 'Methods', 'URI'], $unprotectedRows);
        }

        $inconsistentRows = collect((array) ($report['inconsistent_permissions'] ?? []))
            ->map(fn (array $route) => [
                (string) ($route['name'] ?? 'unnamed'),
                (string) ($route['permission'] ?? ''),
                (string) ($route['expected_permission'] ?? ''),
            ])
            ->values()
            ->toArray();

        if ($inconsistentRows !== []) {
            $this->line('');
            $this->warn('Routes with inconsistent permission mapping:');
            $this->table(['Route', 'Actual', 'Expected'], $inconsistentRows);
        }

        if ((bool) $this->option('save')) {
            $paths = RbacAuditService::writeReport($report);
            $this->line('');
            $this->info('Reports generated:');
            $this->line('- JSON: ' . $paths['json']);
            $this->line('- Markdown: ' . $paths['markdown']);
        }

        $ok = ((int) ($summary['sensitive_routes_unprotected'] ?? 0) === 0)
            && ((int) ($summary['inconsistent_routes'] ?? 0) === 0);

        if ($ok) {
            $this->info('RBAC coverage status: OK');
            return self::SUCCESS;
        }

        $this->error('RBAC coverage status: NOK');
        return self::FAILURE;
    }
}
