<?php

namespace App\Services;

use Illuminate\Support\Facades\Event;

/**
 * CatminEventBus
 *
 * Lightweight hook/event bus built on top of Laravel Event facade.
 * Modules and addons can register listeners in hooks.php files.
 */
class CatminEventBus
{
    /**
     * @var array<string, int>
     */
    protected static array $listenerCounts = [];

    public const ADDON_INSTALLED = 'catmin.addon.installed';
    public const ADDON_ENABLED = 'catmin.addon.enabled';
    public const ADDON_DISABLED = 'catmin.addon.disabled';
    public const ADDON_UNINSTALLED = 'catmin.addon.uninstalled';
    public const ADDON_BOOTING = 'catmin.addon.booting';
    public const ADDON_BOOTED = 'catmin.addon.booted';
    public const MODULE_ENABLED = 'catmin.module.enabled';
    public const MODULE_DISABLED = 'catmin.module.disabled';
    public const CONTENT_CREATED = 'catmin.content.created';
    public const CONTENT_UPDATED = 'catmin.content.updated';
    public const USER_CREATED = 'catmin.user.created';
    public const USER_UPDATED = 'catmin.user.updated';
    public const USER_DELETED = 'catmin.user.deleted';
    public const PAGE_PUBLISHED = 'catmin.page.published';
    public const PAGE_UPDATED = 'catmin.page.updated';
    public const ARTICLE_PUBLISHED = 'catmin.article.published';
    public const ARTICLE_UPDATED = 'catmin.article.updated';
    public const SETTING_UPDATED = 'catmin.setting.updated';
    public const AUTH_LOGIN_SUCCEEDED = 'catmin.auth.login.succeeded';
    public const AUTH_LOGIN_FAILED = 'catmin.auth.login.failed';
    public const AUTH_LOGOUT = 'catmin.auth.logout';
    public const AUTH_2FA_CHALLENGE_PASSED = 'catmin.auth.2fa.challenge.passed';
    public const AUTH_2FA_CHALLENGE_FAILED = 'catmin.auth.2fa.challenge.failed';
    public const SECURITY_RATE_LIMIT_HIT = 'catmin.security.rate_limit.hit';
    public const SYSTEM_HEALTH_CHECKED = 'catmin.system.health.checked';

    /**
     * Register a listener for a CATMIN event name.
     */
    public static function listen(string $eventName, callable $listener): void
    {
        self::$listenerCounts[$eventName] = (self::$listenerCounts[$eventName] ?? 0) + 1;

        Event::listen($eventName, function (...$payload) use ($listener): void {
            $listener(self::normalizePayload($payload));
        });
    }

    /**
     * @param array<int, mixed> $payload
     * @return array<string|int, mixed>
     */
    protected static function normalizePayload(array $payload): array
    {
        if (count($payload) === 1 && is_array($payload[0])) {
            $first = $payload[0];

            if (array_is_list($first) && isset($first[0]) && is_array($first[0])) {
                return $first[0];
            }

            return $first;
        }

        return $payload;
    }

    /**
     * Register multiple listeners in one pass.
     *
     * @param array<string, callable|array<int, callable>> $listeners
     */
    public static function subscribe(array $listeners): void
    {
        foreach ($listeners as $eventName => $eventListeners) {
            if (is_callable($eventListeners)) {
                self::listen($eventName, $eventListeners);
                continue;
            }

            foreach ($eventListeners as $listener) {
                if (is_callable($listener)) {
                    self::listen($eventName, $listener);
                }
            }
        }
    }

    /**
     * Dispatch a CATMIN event and payload.
     *
     * @param array<string, mixed> $payload
     */
    public static function dispatch(string $eventName, array $payload = []): void
    {
        Event::dispatch($eventName, $payload);
    }

    /**
     * @return array<int, string>
     */
    public static function events(): array
    {
        $reflection = new \ReflectionClass(static::class);

        return collect($reflection->getConstants())
            ->filter(fn ($value) => is_string($value) && str_starts_with($value, 'catmin.'))
            ->values()
            ->all();
    }

    public static function listenersCount(string $eventName): int
    {
        return self::$listenerCounts[$eventName] ?? 0;
    }

    /**
     * @return array<int, array{name: string, listeners: int}>
     */
    public static function registry(): array
    {
        return collect(self::events())
            ->map(fn (string $eventName) => [
                'name' => $eventName,
                'listeners' => self::listenersCount($eventName),
            ])
            ->values()
            ->all();
    }
}
