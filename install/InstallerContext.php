<?php

declare(strict_types=1);

namespace Install;

final class InstallerContext
{
    public function __construct(
        private array $data = [],
        private array $completed = [],
        private string $currentStep = 'precheck',
        private array $meta = [],
        private string $state = 'not_started'
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            is_array($payload['data'] ?? null) ? $payload['data'] : [],
            is_array($payload['completed'] ?? null) ? array_values(array_map('strval', $payload['completed'])) : [],
            (string) ($payload['current_step'] ?? 'precheck'),
            is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            (string) ($payload['state'] ?? 'not_started')
        );
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'completed' => $this->completed,
            'current_step' => $this->currentStep,
            'meta' => $this->meta,
            'state' => $this->state,
        ];
    }

    public function data(string $step = null): mixed
    {
        if ($step === null) {
            return $this->data;
        }

        return $this->data[$step] ?? [];
    }

    public function setStepData(string $step, array $payload): void
    {
        $this->data[$step] = $payload;
    }

    public function clearStepData(string $step): void
    {
        unset($this->data[$step]);
    }

    public function completed(): array
    {
        return $this->completed;
    }

    public function markCompleted(string $step): void
    {
        if (!in_array($step, $this->completed, true)) {
            $this->completed[] = $step;
        }
    }

    public function unmarkCompleted(string $step): void
    {
        $this->completed = array_values(array_filter(
            $this->completed,
            static fn (string $done): bool => $done !== $step
        ));
    }

    public function currentStep(): string
    {
        return $this->currentStep;
    }

    public function setCurrentStep(string $step): void
    {
        $this->currentStep = $step;
    }

    public function setMeta(string $key, mixed $value): void
    {
        $this->meta[$key] = $value;
    }

    public function clearMeta(string $key): void
    {
        unset($this->meta[$key]);
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    public function sanitizeSecrets(): void
    {
        if (isset($this->data['database']['password'])) {
            $this->data['database']['password'] = '__redacted__';
        }

        if (isset($this->data['superadmin']['password'])) {
            $this->data['superadmin']['password'] = '__redacted__';
        }
    }

    public function state(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $allowed = ['not_started', 'in_progress', 'step_validated', 'executing', 'completed', 'failed', 'locked'];
        $this->state = in_array($state, $allowed, true) ? $state : 'in_progress';
    }
}
