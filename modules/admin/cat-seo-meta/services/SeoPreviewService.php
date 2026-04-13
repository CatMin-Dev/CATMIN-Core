<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

final class SeoPreviewService
{
    public function build(array $input): array
    {
        $title = trim((string) ($input['seo_title'] ?? ''));
        $desc = trim((string) ($input['meta_description'] ?? ''));

        if ($title === '') {
            $title = trim((string) ($input['entity_title'] ?? 'Untitled content'));
        }
        if ($desc === '') {
            $desc = 'Add a focused meta description to improve click-through rate and AI snippets.';
        }

        return [
            'title' => $title,
            'description' => $desc,
            'url' => trim((string) ($input['canonical_url'] ?? $input['entity_url'] ?? '/')),
            'og_title' => trim((string) ($input['og_title'] ?? '')) !== '' ? trim((string) $input['og_title']) : $title,
            'og_description' => trim((string) ($input['og_description'] ?? '')) !== '' ? trim((string) $input['og_description']) : $desc,
            'og_image_media_id' => (int) ($input['og_image_media_id'] ?? 0),
        ];
    }
}
