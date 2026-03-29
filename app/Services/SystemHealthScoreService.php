<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class SystemHealthScoreService
{
    /**
     * @param array<int, array<string, mixed>> $checks
     * @param array<int, array<string, mixed>> $history
     * @return array<string, mixed>
     */
    public function build(array $checks, array $history = []): array
    {
        $domains = (array) config('catmin.health_score.domains', []);
        $multipliers = (array) config('catmin.health_score.status_multipliers', []);
        $checks = [...$checks, ...$this->contributorChecks()];

        $evaluated = collect($checks)
            ->map(fn (array $check): ?array => $this->evaluateCheck($check, $domains, $multipliers))
            ->filter()
            ->values();

        $score = max(0, 100 - (int) $evaluated->sum('penalty'));
        $worstStatus = $this->worstStatus($evaluated);
        $summary = $this->scoreSummary($score);
        $trend = $this->trend($history, $score, $worstStatus);
        $recommendations = $this->recommendations($evaluated);
        $factors = $evaluated
            ->filter(fn (array $row): bool => (int) $row['penalty'] > 0)
            ->sortByDesc('penalty')
            ->take(max(1, (int) config('catmin.health_score.factor_limit', 5)))
            ->values()
            ->all();

        return [
            'score' => $score,
            'status' => $worstStatus,
            'label' => $summary['label'],
            'badge' => $summary['badge'],
            'confidence' => $this->confidence($evaluated, $domains),
            'penalties_total' => (int) $evaluated->sum('penalty'),
            'factors' => $factors,
            'recommendations' => $recommendations,
            'domains' => $evaluated->values()->all(),
            'diagnostics' => [
                'critical' => $evaluated->where('status', 'critical')->count(),
                'degraded' => $evaluated->where('status', 'degraded')->count(),
                'warning' => $evaluated->where('status', 'warning')->count(),
                'ok' => $evaluated->where('status', 'ok')->count(),
                'participating_domains' => $evaluated->count(),
            ],
            'trend' => $trend,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param array<string, mixed> $check
     * @param array<string, mixed> $domains
     * @param array<string, mixed> $multipliers
     * @return array<string, mixed>|null
     */
    private function evaluateCheck(array $check, array $domains, array $multipliers): ?array
    {
        $domain = trim((string) ($check['domain'] ?? ''));
        if ($domain === '' || !isset($domains[$domain])) {
            return null;
        }

        $status = (string) ($check['status'] ?? 'ok');
        $weight = max(0, (int) Arr::get($domains, $domain . '.weight', 0));
        $penalty = (int) ceil($weight * (float) ($multipliers[$status] ?? 0));
        $actions = collect((array) ($check['actions'] ?? []))
            ->filter(fn (array $action): bool => trim((string) ($action['url'] ?? '')) !== '')
            ->values();

        return [
            'domain' => $domain,
            'label' => (string) Arr::get($domains, $domain . '.label', ucfirst($domain)),
            'status' => $status,
            'weight' => $weight,
            'penalty' => $penalty,
            'title' => (string) ($check['title'] ?? ucfirst($domain)),
            'message' => (string) ($check['message'] ?? ''),
            'metric' => (int) ($check['metric'] ?? 0),
            'threshold' => (int) ($check['threshold'] ?? 0),
            'checked_at' => (string) ($check['checked_at'] ?? now()->toIso8601String()),
            'actions' => $actions->all(),
            'primary_action' => $actions->first(),
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $evaluated
     * @return array<int, array<string, mixed>>
     */
    private function recommendations(Collection $evaluated): array
    {
        return $evaluated
            ->filter(fn (array $row): bool => (int) $row['penalty'] > 0)
            ->sortByDesc('penalty')
            ->take(max(1, (int) config('catmin.health_score.recommendation_limit', 4)))
            ->map(function (array $row): array {
                $action = is_array($row['primary_action'] ?? null) ? $row['primary_action'] : null;

                return [
                    'severity' => $row['status'] === 'critical' ? 'critical' : 'warning',
                    'title' => (string) $row['title'],
                    'message' => $this->recommendationMessage($row, $action),
                    'url' => (string) ($action['url'] ?? ''),
                    'permission' => null,
                    'penalty' => (int) $row['penalty'],
                    'domain' => (string) $row['domain'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $action
     */
    private function recommendationMessage(array $row, ?array $action): string
    {
        $metric = (int) ($row['metric'] ?? 0);
        $threshold = (int) ($row['threshold'] ?? 0);
        $message = trim((string) ($row['message'] ?? ''));
        $actionLabel = trim((string) ($action['label'] ?? ''));

        $parts = [];
        if ($metric > 0 || $threshold > 0) {
            $parts[] = sprintf('Mesure %d pour seuil %d.', $metric, $threshold);
        }
        if ($message !== '') {
            $parts[] = $message;
        }
        if ($actionLabel !== '') {
            $parts[] = 'Action recommandee: ' . $actionLabel . '.';
        }

        return trim(implode(' ', $parts));
    }

    /**
     * @param Collection<int, array<string, mixed>> $evaluated
     */
    private function worstStatus(Collection $evaluated): string
    {
        $ranks = ['ok' => 0, 'warning' => 1, 'degraded' => 2, 'critical' => 3];
        $maxRank = 0;

        foreach ($evaluated as $row) {
            $maxRank = max($maxRank, (int) ($ranks[(string) ($row['status'] ?? 'ok')] ?? 0));
        }

        return (string) (array_search($maxRank, $ranks, true) ?: 'ok');
    }

    /**
     * @return array<string, string>
     */
    private function scoreSummary(int $score): array
    {
        $thresholds = (array) config('catmin.health_score.thresholds', []);
        $excellent = (int) ($thresholds['excellent'] ?? 90);
        $stable = (int) ($thresholds['stable'] ?? 75);
        $warning = (int) ($thresholds['warning'] ?? 55);

        return match (true) {
            $score >= $excellent => ['label' => 'Excellent', 'badge' => 'success'],
            $score >= $stable => ['label' => 'Stable', 'badge' => 'primary'],
            $score >= $warning => ['label' => 'Warning', 'badge' => 'warning'],
            default => ['label' => 'Critical', 'badge' => 'danger'],
        };
    }

    /**
     * @param Collection<int, array<string, mixed>> $evaluated
     * @param array<string, mixed> $domains
     */
    private function confidence(Collection $evaluated, array $domains): int
    {
        $declared = max(1, count($domains));

        return max(0, min(100, (int) round(($evaluated->count() / $declared) * 100)));
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return array<string, mixed>
     */
    private function trend(array $history, int $currentScore, string $currentStatus): array
    {
        $previous = $history[0] ?? null;
        if (!is_array($previous)) {
            return [
                'direction' => 'steady',
                'delta' => 0,
                'message' => 'Premiere mesure exploitable.',
                'previous_score' => null,
                'previous_status' => null,
            ];
        }

        $previousScore = (int) ($previous['score'] ?? $currentScore);
        $previousStatus = (string) ($previous['status'] ?? $currentStatus);
        $delta = $currentScore - $previousScore;
        $direction = $delta >= 4 ? 'up' : ($delta <= -4 ? 'down' : 'steady');
        $message = $direction === 'up'
            ? 'Le score s ameliore par rapport au dernier snapshot.'
            : ($direction === 'down'
                ? 'Le score se degrade par rapport au dernier snapshot.'
                : 'Le score reste globalement stable.');

        if ($previousStatus !== $currentStatus) {
            $message .= ' Changement de statut: ' . strtoupper($previousStatus) . ' -> ' . strtoupper($currentStatus) . '.';
        }

        return [
            'direction' => $direction,
            'delta' => $delta,
            'message' => $message,
            'previous_score' => $previousScore,
            'previous_status' => $previousStatus,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function contributorChecks(): array
    {
        $rows = [];

        foreach ((array) config('catmin.health_score.contributors', []) as $contributorClass) {
            if (!is_string($contributorClass) || $contributorClass === '' || !class_exists($contributorClass)) {
                continue;
            }

            $contributor = app($contributorClass);
            if (!method_exists($contributor, 'contribute')) {
                continue;
            }

            $result = $contributor->contribute();
            foreach (Arr::wrap($result) as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }
}