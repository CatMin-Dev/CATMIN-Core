<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

final class SeoIntegrationService
{
    public function targetConsumerModules(): array
    {
        return ['cat-page', 'cat-blog', 'cat-directory'];
    }

    public function mandatoryDependenciesForConsumer(): array
    {
        return ['cat-slug', 'cat-seo-meta'];
    }

    public function panelHookName(): string
    {
        return 'content.editor.panels';
    }

    public function buildPanelDescriptor(): array
    {
        return [
            'key' => 'seo',
            'label' => 'SEO',
            'view' => CATMIN_MODULES . '/admin/cat-seo-meta/views/embedded_panel.php',
            'order' => 80,
            'fields' => $this->panelFields(),
            'required_modules' => $this->mandatoryDependenciesForConsumer(),
        ];
    }

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
