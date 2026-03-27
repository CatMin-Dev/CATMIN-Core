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

    /**
     * Register a listener for a CATMIN event name.
     */
    public static function listen(string $eventName, callable $listener): void
    {
        Event::listen($eventName, function ($payload) use ($listener): void {
            $listener((array) $payload);
        });
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
}
