<?php

declare(strict_types=1);

namespace Modules\CatSlug\services;

final class SlugNormalizerService
{
    public function normalize(string $value, string $separator = '-', bool $lowercaseOnly = true, bool $transliterationEnabled = true): string
    {
        $separator = $separator === '' ? '-' : substr($separator, 0, 1);
        $text = trim($value);
        if ($text === '') {
            return '';
        }

        if ($transliterationEnabled) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        $text = preg_replace('/[^a-zA-Z0-9]+/', $separator, $text) ?? '';
        $quotedSeparator = preg_quote($separator, '/');
        $text = preg_replace('/' . $quotedSeparator . '+/', $separator, $text) ?? '';
        $text = trim($text, $separator);

        if ($lowercaseOnly) {
            $text = strtolower($text);
        }

        return $text;
    }
}
