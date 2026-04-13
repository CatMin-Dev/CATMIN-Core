<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

final class SeoIntegrationService
{
    public function panelFields(): array
    {
        return [
            'seo_title',
            'meta_description',
            'canonical_url',
            'robots_index',
            'robots_follow',
            'og_title',
            'og_description',
            'og_image_media_id',
            'focus_keyword',
        ];
    }

    public function settingsKeys(): array
    {
        return [
            'seo.default_title_pattern',
            'seo.default_meta_pattern',
            'seo.enable_score',
            'seo.enable_social_preview',
            'seo.enable_canonical',
            'seo.enable_structured_data_later',
            'seo.enable_sitemap_later',
        ];
    }
}
