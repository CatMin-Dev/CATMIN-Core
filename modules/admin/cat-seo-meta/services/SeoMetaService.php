<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

use Modules\CatSeoMeta\repositories\SeoMetaRepository;

final class SeoMetaService
{
    public function __construct(
        private readonly SeoMetaRepository $repository,
        private readonly SeoScoreService $scoreService,
        private readonly SeoAuditService $auditService,
        private readonly SeoPreviewService $previewService,
        private readonly SeoKeywordSuggestService $keywordSuggestService
    ) {
    }

    public function save(array $payload): array
    {
        $entityType = strtolower(trim((string) ($payload['entity_type'] ?? '')));
        $entityId = (int) ($payload['entity_id'] ?? 0);

        if ($entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => 'entity_type and entity_id are required'];
        }

        $normalized = $this->normalize($payload);
        $locale = strtolower(trim((string) ($payload['locale'] ?? config('app.locale', 'fr'))));
        $keywords = $this->keywordSuggestService->suggest($payload, $locale);
        if (($normalized['focus_keyword'] ?? '') === '' && ($keywords['focus_keyword'] ?? '') !== '') {
            $normalized['focus_keyword'] = (string) ($keywords['focus_keyword'] ?? '');
        }
        $audit = $this->auditService->audit($normalized);

        $normalized['seo_score'] = (int) ($audit['score'] ?? 0);
        $normalized['seo_flags_json'] = json_encode($audit['flags'] ?? [], JSON_UNESCAPED_SLASHES);

        $this->repository->upsert($normalized);

        return [
            'ok' => true,
            'message' => 'SEO metadata saved',
            'score' => (int) $normalized['seo_score'],
            'flags' => $audit['flags'] ?? [],
            'keywords' => $keywords,
        ];
    }

    public function get(string $entityType, int $entityId): array
    {
        $row = $this->repository->find(strtolower(trim($entityType)), $entityId);
        if ($row === null) {
            return $this->normalize([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'robots_index' => 1,
                'robots_follow' => 1,
            ]);
        }

        return $this->normalize($row);
    }

    public function dashboardState(): array
    {
        return [
            'stats' => $this->repository->stats(),
            'needs_attention' => $this->repository->needsAttention(20),
            'recent' => $this->repository->recent(20),
        ];
    }

    public function auditOnly(array $payload): array
    {
        $normalized = $this->normalize($payload);
        return $this->auditService->audit($normalized);
    }

    public function preview(array $payload): array
    {
        return $this->previewService->build($this->normalize($payload));
    }

    private function normalize(array $payload): array
    {
        return [
            'entity_type' => strtolower(trim((string) ($payload['entity_type'] ?? ''))),
            'entity_id' => (int) ($payload['entity_id'] ?? 0),
            'seo_title' => trim((string) ($payload['seo_title'] ?? '')),
            'meta_description' => trim((string) ($payload['meta_description'] ?? '')),
            'canonical_url' => trim((string) ($payload['canonical_url'] ?? '')),
            'robots_index' => !empty($payload['robots_index']) ? 1 : 0,
            'robots_follow' => !empty($payload['robots_follow']) ? 1 : 0,
            'og_title' => trim((string) ($payload['og_title'] ?? '')),
            'og_description' => trim((string) ($payload['og_description'] ?? '')),
            'og_image_media_id' => (int) ($payload['og_image_media_id'] ?? 0),
            'focus_keyword' => trim((string) ($payload['focus_keyword'] ?? '')),
            'seo_score' => (int) ($payload['seo_score'] ?? 0),
            'seo_flags_json' => is_string($payload['seo_flags_json'] ?? null) ? $payload['seo_flags_json'] : '',
            'updated_at' => (string) ($payload['updated_at'] ?? ''),
        ];
    }
}
