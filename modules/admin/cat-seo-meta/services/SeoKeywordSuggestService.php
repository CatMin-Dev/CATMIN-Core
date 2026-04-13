<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\services;

final class SeoKeywordSuggestService
{
    public function suggest(array $input, string $locale = 'fr'): array
    {
        $locale = strtolower(trim($locale));
        if (!in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $text = strtolower(trim(implode(' ', [
            (string) ($input['seo_title'] ?? ''),
            (string) ($input['meta_description'] ?? ''),
            (string) ($input['entity_title'] ?? ''),
            (string) ($input['content_body'] ?? ''),
        ])));

        if ($text === '') {
            return ['focus_keyword' => '', 'secondary_keywords' => []];
        }

        $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text) ?? $text;
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopwords = $this->stopwords($locale);
        $counts = [];
        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '' || mb_strlen($token) < 3 || isset($stopwords[$token])) {
                continue;
            }
            $counts[$token] = ($counts[$token] ?? 0) + 1;
        }

        if ($counts === []) {
            return ['focus_keyword' => '', 'secondary_keywords' => []];
        }

        arsort($counts);
        $top = array_keys($counts);
        $focus = (string) ($top[0] ?? '');
        $secondary = array_slice($top, 1, 5);

        return [
            'focus_keyword' => $focus,
            'secondary_keywords' => array_values($secondary),
        ];
    }

    private function stopwords(string $locale): array
    {
        $fr = ['les','des','une','dans','avec','pour','sur','plus','sans','entre','vous','nous','elle','elles','ils','que','qui','quoi','dont','comme','mais','donc','afin','etre','avoir','cet','cette','ces','aux','par','pas','est','sont','du','de','la','le','un','et'];
        $en = ['the','and','for','with','from','that','this','are','was','were','have','has','you','your','our','but','not','can','will','into','than','then','such','also','about','over','under','between','of','to','in','on','at'];
        $list = $locale === 'en' ? $en : $fr;
        $map = [];
        foreach ($list as $word) {
            $map[$word] = true;
        }
        return $map;
    }
}
