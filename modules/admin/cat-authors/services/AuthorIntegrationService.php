<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

final class AuthorIntegrationService
{
    private const CONSUMERS = ['cat-blog', 'cat-page', 'cat-directory'];

    public function appendPanel(array $panels, array $context): array
    {
        $moduleSlug = strtolower(trim((string) ($context['module_slug'] ?? '')));
        if ($moduleSlug !== '' && !in_array($moduleSlug, self::CONSUMERS, true)) {
            return $panels;
        }
        $panels[] = $this->buildPanelDescriptor();
        return $panels;
    }

    public function buildPanelDescriptor(): array
    {
        return [
            'id'          => 'cat-authors-panel',
            'label'       => 'Auteur',
            'module_slug' => 'cat-authors',
            'embed_path'  => '/modules/author-bridge/panel',
            'order'       => 90,
            'icon'        => 'person-badge',
        ];
    }
}
