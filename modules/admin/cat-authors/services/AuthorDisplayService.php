<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

final class AuthorDisplayService
{
    /** Returns a minimal safe display array from a raw profile row */
    public function format(?array $profile): ?array
    {
        if ($profile === null) {
            return null;
        }
        $socials = [];
        $raw = $profile['socials_json'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $socials = $decoded;
            }
        }
        return [
            'id'           => (int) $profile['id'],
            'display_name' => htmlspecialchars((string) $profile['display_name'], ENT_QUOTES, 'UTF-8'),
            'slug'         => (string) $profile['slug'],
            'bio'          => htmlspecialchars((string) ($profile['bio'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'website_url'  => filter_var((string) ($profile['website_url'] ?? ''), FILTER_VALIDATE_URL) ?: '',
            'socials'      => $socials,
            'visibility'   => (string) ($profile['visibility'] ?? 'public'),
            'username'     => (string) ($profile['username'] ?? ''),
        ];
    }
}
