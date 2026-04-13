<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

final class AuthorDisplayService
{
    public function format(?array $profile): ?array
    {
        if ($profile === null) {
            return null;
        }

        return [
            'id'           => (int) $profile['id'],
            'user_id'      => (int) ($profile['user_id'] ?? 0),
            'first_name'   => htmlspecialchars((string) ($profile['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'last_name'    => htmlspecialchars((string) ($profile['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'display_name' => htmlspecialchars((string) $profile['display_name'], ENT_QUOTES, 'UTF-8'),
            'slug'         => (string) $profile['slug'],
            'bio'          => htmlspecialchars((string) ($profile['bio'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'socials'      => $this->normalizeSocials($profile['socials_json'] ?? null),
            'visibility'   => (string) ($profile['visibility'] ?? 'public'),
            'username'     => (string) ($profile['username'] ?? ''),
        ];
    }

    public function normalizeSocials(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $socials = [];
        $isAssoc = array_keys($raw) !== range(0, count($raw) - 1);

        if ($isAssoc) {
            foreach ($raw as $network => $url) {
                $network = strtolower(trim((string) $network));
                $url = trim((string) $url);
                if ($network === '' || $url === '') {
                    continue;
                }
                $socials[] = ['network' => $network, 'url' => $url];
            }
            return $socials;
        }

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $network = strtolower(trim((string) ($item['network'] ?? '')));
            $url = trim((string) ($item['url'] ?? ''));
            if ($network === '' || $url === '') {
                continue;
            }
            $socials[] = ['network' => $network, 'url' => $url];
        }

        return $socials;
    }
}
