<?php

namespace Modules\Webhooks\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Models\WebhookDelivery;

class WebhookAdminService
{
    /** Available event types that can be subscribed to. */
    public static function availableEvents(): array
    {
        return [
            'user.created',
            'user.updated',
            'user.deleted',
            'page.published',
            'page.updated',
            'article.published',
            'article.updated',
            'media.uploaded',
            'media.deleted',
            'settings.updated',
        ];
    }

    public static function listing(): LengthAwarePaginator
    {
        return Webhook::query()
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public static function create(array $data): Webhook
    {
        return Webhook::query()->create([
            'name' => $data['name'],
            'url' => $data['url'],
            'events' => $data['events'] ?? [],
            'secret' => $data['secret'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public static function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->update([
            'name' => $data['name'],
            'url' => $data['url'],
            'events' => $data['events'] ?? [],
            'secret' => $data['secret'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return $webhook->fresh() ?? $webhook;
    }

    public static function delete(Webhook $webhook): void
    {
        $webhook->delete();
    }

    public static function find(int $id): ?Webhook
    {
        return Webhook::find($id);
    }

    public static function bulkActivate(array $ids): int
    {
        return Webhook::whereIn('id', $ids)->update(['status' => 'active']);
    }

    public static function bulkDeactivate(array $ids): int
    {
        return Webhook::whereIn('id', $ids)->update(['status' => 'inactive']);
    }

    public static function bulkDelete(array $ids): int
    {
        $count = 0;
        foreach (Webhook::whereIn('id', $ids)->get() as $webhook) {
            self::delete($webhook);
            $count++;
        }
        return $count;
    }

    /**
     * Return dead-letter deliveries ordered by most recent first.
     */
    public static function listDeadLetterQueue(int $perPage = 25): LengthAwarePaginator
    {
        return WebhookDelivery::query()
            ->where('status', 'dead_letter')
            ->orderByDesc('dead_letter_at')
            ->paginate($perPage);
    }

    /**
     * Purge all dead-letter deliveries (older than $olderThanDays days).
     */
    public static function purgeDeadLetterQueue(int $olderThanDays = 30): int
    {
        return WebhookDelivery::query()
            ->where('status', 'dead_letter')
            ->where('dead_letter_at', '<=', now()->subDays($olderThanDays))
            ->delete();
    }
}
