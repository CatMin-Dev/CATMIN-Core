<?php

namespace App\Services;

use Illuminate\Support\HtmlString;

class CatminHookRegistry
{
    /**
     * @var array<string, array<int, callable|string>>
     */
    protected static array $slots = [];

    public static function before(string $name, callable|string $callback): void
    {
        self::register('before:' . $name, $callback);
    }

    public static function after(string $name, callable|string $callback): void
    {
        self::register('after:' . $name, $callback);
    }

    public static function register(string $slot, callable|string $callback): void
    {
        self::$slots[$slot] ??= [];
        self::$slots[$slot][] = $callback;
    }

    public static function render(string $slot, array $context = []): HtmlString
    {
        $fragments = [];

        foreach (self::$slots[$slot] ?? [] as $callback) {
            $output = is_callable($callback) ? $callback($context) : $callback;

            if ($output === null || $output === '') {
                continue;
            }

            $fragments[] = (string) $output;
        }

        return new HtmlString(implode("\n", $fragments));
    }

    public static function count(string $slot): int
    {
        return count(self::$slots[$slot] ?? []);
    }

    /**
     * @return array<int, array{name: string, callbacks: int}>
     */
    public static function registry(): array
    {
        return collect(self::$slots)
            ->map(fn (array $callbacks, string $slot) => [
                'name' => $slot,
                'callbacks' => count($callbacks),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }
}
