<?php

declare(strict_types=1);

namespace Install;

use RuntimeException;

final class InstallerStateMachine
{
    public const STATES = [
        'not_started',
        'in_progress',
        'step_validated',
        'executing',
        'completed',
        'failed',
        'locked',
    ];

    public const STEPS = [
        'precheck',
        'legal',
        'profile',
        'database',
        'identity',
        'superadmin',
        'security',
        'system',
        'execution',
        'recovery_codes',
        'report',
        'lock',
    ];

    public function hasStep(string $step): bool
    {
        return in_array($step, self::STEPS, true);
    }

    public function hasState(string $state): bool
    {
        return in_array($state, self::STATES, true);
    }

    public function next(string $step): ?string
    {
        $index = $this->indexOf($step);

        return self::STEPS[$index + 1] ?? null;
    }

    public function previous(string $step): ?string
    {
        $index = $this->indexOf($step);

        return $index > 0 ? self::STEPS[$index - 1] : null;
    }

    public function canAccess(string $requestedStep, InstallerContext $context): bool
    {
        $requestedIndex = $this->indexOf($requestedStep);

        $maxCompleted = -1;
        foreach ($context->completed() as $step) {
            if (!$this->hasStep($step)) {
                continue;
            }

            $index = $this->indexOf($step);
            if ($index > $maxCompleted) {
                $maxCompleted = $index;
            }
        }

        return $requestedIndex <= ($maxCompleted + 1);
    }

    public function firstPending(InstallerContext $context): string
    {
        foreach (self::STEPS as $step) {
            if (!in_array($step, $context->completed(), true)) {
                return $step;
            }
        }

        return 'lock';
    }

    private function indexOf(string $step): int
    {
        $index = array_search($step, self::STEPS, true);
        if (!is_int($index)) {
            throw new RuntimeException('Unknown installer step: ' . $step);
        }

        return $index;
    }
}
