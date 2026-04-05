<?php

declare(strict_types=1);

namespace Core\config;

final class EnvManager
{
    /** @var array<string, string> */
    private array $values = [];

    public function loadFile(string $path, bool $override = false): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $position = strpos($line, '=');
            if ($position === false) {
                continue;
            }

            $key = trim(substr($line, 0, $position));
            $value = trim(substr($line, $position + 1));

            if ($key === '') {
                continue;
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $this->set($key, $value, $override);
        }
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function set(string $key, string $value, bool $override = true): void
    {
        if (!$override && $this->get($key) !== null) {
            return;
        }

        $this->values[$key] = $value;
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->values;
    }
}
