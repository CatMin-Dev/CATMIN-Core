<?php

namespace Modules\SEO\Services;

use Modules\SEO\Models\SeoMeta;

class SeoAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SeoMeta>
     */
    public function listing()
    {
        return SeoMeta::query()
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): SeoMeta
    {
        /** @var SeoMeta $seoMeta */
        $seoMeta = SeoMeta::query()->create($this->normalize($payload));

        return $seoMeta;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(SeoMeta $seoMeta, array $payload): SeoMeta
    {
        $seoMeta->fill($this->normalize($payload));
        $seoMeta->save();

        return $seoMeta;
    }

    public function findFor(string $targetType, int $targetId): ?SeoMeta
    {
        return SeoMeta::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload): array
    {
        return [
            'target_type' => $payload['target_type'] ?: null,
            'target_id' => $payload['target_id'] ?: null,
            'meta_title' => $payload['meta_title'] ?: null,
            'meta_description' => $payload['meta_description'] ?: null,
            'meta_robots' => $payload['meta_robots'] ?: null,
            'canonical_url' => $payload['canonical_url'] ?: null,
            'slug_override' => $payload['slug_override'] ?: null,
        ];
    }
}
