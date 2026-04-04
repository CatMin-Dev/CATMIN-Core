<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use Illuminate\Support\Str;

/**
 * Handles safe HTML rendering for the public frontend.
 *
 * Delegates block-injection to the existing inject_blocks() helper and
 * provides small utility methods (excerpt, plain text) used by views.
 */
final class PublicContentRenderService
{
    /**
     * Render HTML content intended for public display.
     *
     * Injects {{ block:slug }} placeholders and returns the content as-is
     * otherwise. Callers are responsible for escaping if needed — Blade's
     * {!! !!} is expected here because the content is CMS-authored.
     */
    public function render(string $content): string
    {
        if ($content === '') {
            return '';
        }

        return inject_blocks($content);
    }

    /**
     * Return a plain-text excerpt from HTML content.
     */
    public function excerpt(string $content, int $maxLength = 200): string
    {
        $plain = strip_tags($content);
        $plain = (string) preg_replace('/\s+/', ' ', $plain);

        return Str::limit(trim($plain), $maxLength);
    }

    /**
     * Return estimated reading time in minutes (200 wpm).
     */
    public function readingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return (int) max(1, ceil($wordCount / 200));
    }
}
