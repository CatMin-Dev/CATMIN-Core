<?php

declare(strict_types=1);

final class CoreModuleRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $modules = [];

    /** @var array<int, string> */
    private array $errors = [];

    public function add(array $module): void
    {
        $slug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
        if ($slug === '') {
            $this->errors[] = 'Module sans slug ignoré';
            return;
        }

        if (isset($this->modules[$slug])) {
            $this->errors[] = 'Collision critique slug: ' . $slug;
            return;
        }

        $this->modules[$slug] = $module;
    }

    public function all(): array
    {
        return array_values($this->modules);
    }

    public function bySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        return $this->modules[$slug] ?? null;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}

