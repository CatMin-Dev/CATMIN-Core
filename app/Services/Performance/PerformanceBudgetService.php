<?php

namespace App\Services\Performance;

use Illuminate\Http\Request;

class PerformanceBudgetService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function budgets(): array
    {
        $budgets = config('catmin.performance.budgets', []);

        return collect(is_array($budgets) ? $budgets : [])
            ->filter(fn ($budget): bool => is_array($budget))
            ->map(function (array $budget): array {
                return [
                    'key' => (string) ($budget['key'] ?? ''),
                    'label' => (string) ($budget['label'] ?? 'Budget'),
                    'category' => (string) ($budget['category'] ?? 'general'),
                    'route' => (string) ($budget['route'] ?? ''),
                    'path' => (string) ($budget['path'] ?? ''),
                    'target_response_ms' => (int) ($budget['target_response_ms'] ?? 0),
                    'max_response_ms' => (int) ($budget['max_response_ms'] ?? 0),
                    'max_queries' => (int) ($budget['max_queries'] ?? 0),
                    'max_slow_queries' => (int) ($budget['max_slow_queries'] ?? 0),
                    'notes' => (string) ($budget['notes'] ?? ''),
                ];
            })
            ->filter(fn (array $budget): bool => $budget['key'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    public function budgetForContext(array $context): ?array
    {
        return $this->findBudget(
            (string) ($context['route_name'] ?? ''),
            ltrim((string) ($context['path'] ?? ''), '/')
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function budgetForRequest(Request $request): ?array
    {
        $routeName = (string) optional($request->route())->getName();

        return $this->findBudget($routeName, ltrim((string) $request->path(), '/'));
    }

    /**
     * @param array<string, mixed> $budget
     * @param array<string, mixed> $metrics
     * @return array<string, mixed>
     */
    public function evaluate(array $budget, array $metrics): array
    {
        $breaches = [];

        $durationMs = (int) ($metrics['duration_ms'] ?? 0);
        $queryCount = (int) ($metrics['query_count'] ?? 0);
        $slowQueryCount = (int) ($metrics['slow_query_count'] ?? 0);

        if ($budget['max_response_ms'] > 0 && $durationMs > $budget['max_response_ms']) {
            $breaches[] = 'response_ms';
        }

        if ($budget['max_queries'] > 0 && $queryCount > $budget['max_queries']) {
            $breaches[] = 'query_count';
        }

        if ($budget['max_slow_queries'] >= 0 && $slowQueryCount > $budget['max_slow_queries']) {
            $breaches[] = 'slow_query_count';
        }

        return [
            'budget' => $budget,
            'breaches' => $breaches,
            'is_breach' => $breaches !== [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findBudget(string $routeName, string $path): ?array
    {
        foreach ($this->budgets() as $budget) {
            $budgetRoute = (string) ($budget['route'] ?? '');
            $budgetPath = ltrim((string) ($budget['path'] ?? ''), '/');

            if ($budgetRoute !== '' && $budgetRoute === $routeName) {
                return $budget;
            }

            if ($budgetPath !== '' && $budgetPath === $path) {
                return $budget;
            }
        }

        return null;
    }
}