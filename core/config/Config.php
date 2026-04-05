<?php

declare(strict_types=1);

namespace Core\config;

final class Config
{
    private static ?ConfigRepository $repository = null;

    public static function loadDirectory(string $directory): void
    {
        self::repository()->loadDirectory($directory);
    }

    public static function repository(): ConfigRepository
    {
        if (self::$repository === null) {
            self::$repository = new ConfigRepository();
        }

        return self::$repository;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::repository()->get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        self::repository()->set($key, $value);
    }

    public static function all(): array
    {
        return self::repository()->all();
    }
}
