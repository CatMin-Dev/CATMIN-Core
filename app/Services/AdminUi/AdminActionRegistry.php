<?php

namespace App\Services\AdminUi;

class AdminActionRegistry
{
    /** @var array<int,array<string,mixed>> */
    private static array $globalActions = [];

    /** @var array<string,array<int,array<string,mixed>>> */
    private static array $pageActions = [];

    /** @param array<string,mixed> $action */
    public static function registerGlobal(array $action): void
    {
        self::$globalActions[] = $action;
    }

    /** @param array<string,mixed> $action */
    public static function registerForPage(string $routeName, array $action): void
    {
        self::$pageActions[$routeName] = self::$pageActions[$routeName] ?? [];
        self::$pageActions[$routeName][] = $action;
    }

    /** @return array<int,array<string,mixed>> */
    public static function global(): array
    {
        return self::$globalActions;
    }

    /** @return array<int,array<string,mixed>> */
    public static function forPage(string $routeName): array
    {
        return self::$pageActions[$routeName] ?? [];
    }
}
