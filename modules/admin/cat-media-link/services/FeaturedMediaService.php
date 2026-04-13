<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

final class FeaturedMediaService
{
    public function pickFeatured(array $links): ?array
    {
        foreach ($links as $row) {
            if ((string) ($row['link_type'] ?? '') === 'featured' || (int) ($row['is_primary'] ?? 0) === 1) {
                return $row;
            }
        }
        return null;
    }
}
