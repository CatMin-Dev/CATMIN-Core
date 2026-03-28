<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;

class AuditRbacCommand extends Command
{
    protected $signature = 'audit:rbac {--output=}';

    protected $description = 'Generate RBAC audit matrix for all admin routes';

    public function handle(): int
    {
        $this->line('<fg=blue>' . str_repeat('=', 100) . '</>');
        $this->line('<fg=blue>CATMIN RBAC AUDIT MATRIX</>');
        $this->line('<fg=green>Generated: ' . Carbon::now()->format('Y-m-d H:i:s') . '</>');
        $this->line('<fg=green>Development Phase: v2-dev</>');
        $this->line('<fg=blue>' . str_repeat('=', 100) . '</>');
        $this->line('');

        // Collect routes
        $adminRoutes = [];
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();

            // Only audit admin routes
            if (!str_starts_with($uri, 'admin/') && $uri !== 'admin') {
                continue;
            }

            $middleware = $route->middleware();
            $hasPermission = false;
            $permission = null;

            // Check for permission middleware
            foreach ($middleware as $m) {
                if (str_starts_with($m, 'catmin.permission:')) {
                    $hasPermission = true;
                    $permission = str_replace('catmin.permission:', '', $m);
                    break;
                }
            }

            $adminRoutes[] = [
                'name' => $route->getName() ?? 'unnamed',
                'uri' => $uri,
                'methods' => implode('|', array_diff($route->methods(), ['HEAD'])),
                'has_permission' => $hasPermission,
                'permission' => $permission,
            ];
        }

        // Sort by URI
        usort($adminRoutes, fn($a, $b) => strcmp($a['uri'], $b['uri']));

        // Separate protected and unprotected
        $withPermission = array_filter($adminRoutes, fn($r) => $r['has_permission']);
        $withoutPermission = array_filter($adminRoutes, fn($r) => !$r['has_permission']);
        $total = count($adminRoutes);
        $coverage = $total > 0 ? round((count($withPermission) / $total) * 100, 1) : 0;

        // Print routes with permission
        $this->line('<fg=green>ROUTES WITH PERMISSION CHECKS: ' . count($withPermission) . '</>');
        $this->line(str_repeat('-', 100));
        $this->line(sprintf('%-40s | %-30s | %-20s | %-8s', 'Route', 'Permission', 'URI', 'Methods'));
        $this->line(str_repeat('-', 100));

        foreach ($withPermission as $route) {
            $this->line(sprintf('%-40s | %-30s | %-20s | %-8s',
                substr($route['name'], 0, 39),
                substr($route['permission'] ?? '-', 0, 29),
                substr($route['uri'], 0, 19),
                $route['methods']
            ));
        }

        $this->line('');
        $this->line('');

        // Print routes without permission
        $this->line('<fg=red>ROUTES WITHOUT PERMISSION CHECKS: ' . count($withoutPermission) . '</>');
        $this->line(str_repeat('-', 100));
        $this->line(sprintf('%-40s | %-20s | %-8s', 'Route', 'URI', 'Methods'));
        $this->line(str_repeat('-', 100));

        foreach ($withoutPermission as $route) {
            $this->line(sprintf('<fg=yellow>%-40s | %-20s | %-8s</>',
                substr($route['name'], 0, 39),
                substr($route['uri'], 0, 19),
                $route['methods']
            ));
        }

        $this->line('');
        $this->line('');

        // Print summary
        $this->line('<fg=blue>' . str_repeat('=', 100) . '</>');
        $this->line('<fg=blue>SUMMARY:</fg=>');
        $this->line(sprintf('  Total admin routes:       <fg=cyan>%d</>', $total));
        $this->line(sprintf('  With permission:          <fg=green>%d</>', count($withPermission)));
        $this->line(sprintf('  Without permission:       <fg=red>%d</>', count($withoutPermission)));
        $this->line(sprintf('  Coverage percentage:      <fg=blue>%.1f%%</>', $coverage));
        $this->line('<fg=blue>' . str_repeat('=', 100) . '</>');

        // List unique permissions
        $permissions = array_filter(array_unique(array_column($withPermission, 'permission')));
        if (!empty($permissions)) {
            $this->line('');
            $this->line('<fg=cyan>UNIQUE PERMISSIONS USED:</>');
            $this->line(str_repeat('-', 100));
            sort($permissions);
            foreach ($permissions as $p) {
                $count = count(array_filter($withPermission, fn($r) => $r['permission'] === $p));
                $this->line(sprintf('  %-50s <fg=green>%d routes</>', $p, $count));
            }
        }

        if ($output = $this->option('output')) {
            $this->saveMatrix($adminRoutes, $output);
            $this->line('');
            $this->info("Matrix saved to: {$output}");
        }

        $this->line('');

        return 0;
    }

    private function saveMatrix(array $routes, string $output): void
    {
        $withPermission = array_filter($routes, fn($r) => $r['has_permission']);
        $withoutPermission = array_filter($routes, fn($r) => !$r['has_permission']);

        $data = [
            'generated_at' => Carbon::now()->toIso8601String(),
            'phase' => 'v2-dev',
            'summary' => [
                'total_routes' => count($routes),
                'with_permission' => count($withPermission),
                'without_permission' => count($withoutPermission),
                'coverage_percentage' => count($routes) > 0 
                    ? round((count($withPermission) / count($routes)) * 100, 1) 
                    : 0,
            ],
            'protected_routes' => array_values($withPermission),
            'unprotected_routes' => array_values($withoutPermission),
        ];

        file_put_contents($output, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
