<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

final class MediaGalleryService
{
    public function collectGalleryIds(string $csv): array
    {
        $parts = preg_split('/[\s,]+/', trim($csv));
        if (!is_array($parts)) {
            return [];
        }

        $ids = [];
        foreach ($parts as $token) {
            $id = (int) $token;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function buildGalleryLinks(array $ids): array
    {
        $rows = [];
        $order = 1;
        foreach ($ids as $id) {
            $rows[] = [
                'media_id' => (int) $id,
                'link_type' => 'gallery',
                'sort_order' => $order++,
                'is_primary' => false,
            ];
        }
        return $rows;
    }
}
