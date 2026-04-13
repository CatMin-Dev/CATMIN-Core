<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

final class SeoScoreService
{
    public function calculate(array $input): array
    {
        $score = 0;
        $signals = [];

        $title = trim((string) ($input['seo_title'] ?? ''));
        if ($title !== '') {
            $score += 20;
            $titleLen = mb_strlen($title);
            if ($titleLen >= 35 && $titleLen <= 65) {
                $score += 10;
                $signals[] = ['key' => 'title_length_ok', 'weight' => 10, 'status' => 'ok'];
            } else {
                $signals[] = ['key' => 'title_length_suboptimal', 'weight' => 0, 'status' => 'warn'];
            }
            $signals[] = ['key' => 'title_present', 'weight' => 20, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'title_missing', 'weight' => 0, 'status' => 'error'];
        }

        $meta = trim((string) ($input['meta_description'] ?? ''));
        if ($meta !== '') {
            $score += 20;
            $metaLen = mb_strlen($meta);
            if ($metaLen >= 120 && $metaLen <= 170) {
                $score += 10;
                $signals[] = ['key' => 'meta_length_ok', 'weight' => 10, 'status' => 'ok'];
            } else {
                $signals[] = ['key' => 'meta_length_suboptimal', 'weight' => 0, 'status' => 'warn'];
            }
            $signals[] = ['key' => 'meta_present', 'weight' => 20, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'meta_missing', 'weight' => 0, 'status' => 'error'];
        }

        $focus = trim((string) ($input['focus_keyword'] ?? ''));
        if ($focus !== '') {
            $score += 10;
            $haystack = strtolower($title . ' ' . $meta);
            if (str_contains($haystack, strtolower($focus))) {
                $score += 5;
                $signals[] = ['key' => 'focus_used', 'weight' => 5, 'status' => 'ok'];
            } else {
                $signals[] = ['key' => 'focus_not_used', 'weight' => 0, 'status' => 'warn'];
            }
            $signals[] = ['key' => 'focus_present', 'weight' => 10, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'focus_missing', 'weight' => 0, 'status' => 'warn'];
        }

        if (trim((string) ($input['og_title'] ?? '')) !== '') {
            $score += 8;
            $signals[] = ['key' => 'og_title_present', 'weight' => 8, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'og_title_missing', 'weight' => 0, 'status' => 'warn'];
        }

        if (trim((string) ($input['og_description'] ?? '')) !== '') {
            $score += 7;
            $signals[] = ['key' => 'og_description_present', 'weight' => 7, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'og_description_missing', 'weight' => 0, 'status' => 'warn'];
        }

        if ((int) ($input['og_image_media_id'] ?? 0) > 0) {
            $score += 10;
            $signals[] = ['key' => 'og_image_present', 'weight' => 10, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'og_image_missing', 'weight' => 0, 'status' => 'warn'];
        }

        if (trim((string) ($input['canonical_url'] ?? '')) !== '') {
            $score += 5;
            $signals[] = ['key' => 'canonical_present', 'weight' => 5, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'canonical_not_set', 'weight' => 0, 'status' => 'info'];
        }

        if (!empty($input['robots_index']) && !empty($input['robots_follow'])) {
            $score += 5;
            $signals[] = ['key' => 'robots_open', 'weight' => 5, 'status' => 'ok'];
        } else {
            $signals[] = ['key' => 'robots_restrictive', 'weight' => 0, 'status' => 'warn'];
        }

        return [
            'score' => max(0, min(100, $score)),
            'signals' => $signals,
        ];
    }
}
