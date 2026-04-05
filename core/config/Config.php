<?php

declare(strict_types=1);

namespace Core\config;

final class Config
{
    private static array $items = [];

    public static function loadDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (glob(rtrim($directory, '/') . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $data = require $file;
            self::$items[$key] = is_array($data) ? $data : [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$items[$key] = $value;
    }

    public static function all(): array
    {
        return self::$items;
    }
}
