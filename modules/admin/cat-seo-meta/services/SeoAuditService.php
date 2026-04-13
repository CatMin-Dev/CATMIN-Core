<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

final class SeoAuditService
{
    public function __construct(private readonly SeoScoreService $scoreService)
    {
    }

    public function audit(array $input): array
    {
        $scoreState = $this->scoreService->calculate($input);
        $flags = [];

        if (trim((string) ($input['seo_title'] ?? '')) === '') {
            $flags[] = ['type' => 'missing', 'field' => 'seo_title', 'message' => 'SEO title missing'];
        }
        if (trim((string) ($input['meta_description'] ?? '')) === '') {
            $flags[] = ['type' => 'missing', 'field' => 'meta_description', 'message' => 'Meta description missing'];
        }
        if ((int) ($input['og_image_media_id'] ?? 0) <= 0) {
            $flags[] = ['type' => 'missing', 'field' => 'og_image_media_id', 'message' => 'Social image missing'];
        }
        if (trim((string) ($input['focus_keyword'] ?? '')) === '') {
            $flags[] = ['type' => 'warning', 'field' => 'focus_keyword', 'message' => 'Focus keyword not defined'];
        }
        if (!empty($input['robots_index']) || !empty($input['robots_follow'])) {
            // nothing
        } else {
            $flags[] = ['type' => 'warning', 'field' => 'robots', 'message' => 'Robots are closed for index and follow'];
        }

        $summary = $this->quickSummary($scoreState['score'], $flags);

        return [
            'score' => (int) $scoreState['score'],
            'signals' => $scoreState['signals'],
            'flags' => $flags,
            'summary' => $summary,
        ];
    }

    private function quickSummary(int $score, array $flags): string
    {
        if ($score >= 80 && $flags === []) {
            return 'SEO baseline is strong. Keep content fresh and linked.';
        }
        if ($score >= 60) {
            return 'SEO is acceptable but key metadata improvements are recommended.';
        }
        return 'SEO quality is weak. Complete title, description and social signals first.';
    }
}
