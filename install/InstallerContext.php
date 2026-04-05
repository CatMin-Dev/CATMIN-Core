<?php

declare(strict_types=1);

namespace Install;

final class InstallerContext
{
    public function __construct(
        private array $data = [],
        private array $completed = [],
        private string $currentStep = 'precheck',
        private array $meta = []
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            is_array($payload['data'] ?? null) ? $payload['data'] : [],
            is_array($payload['completed'] ?? null) ? array_values(array_map('strval', $payload['completed'])) : [],
            (string) ($payload['current_step'] ?? 'precheck'),
            is_array($payload['meta'] ?? null) ? $payload['meta'] : []
        );
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'completed' => $this->completed,
            'current_step' => $this->currentStep,
            'meta' => $this->meta,
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
}
