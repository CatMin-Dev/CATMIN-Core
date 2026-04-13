<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

final class AuthorSelectorService
{
    public function __construct(private readonly AuthorRepository $repo) {}

    /** Returns a flat list suitable for a <select> element */
    public function listForSelect(): array
    {
        return array_map(static function (array $p): array {
            return [
                'id'           => (int) $p['id'],
                'display_name' => (string) $p['display_name'],
                'slug'         => (string) $p['slug'],
                'username'     => (string) ($p['username'] ?? ''),
                'visibility'   => (string) ($p['visibility'] ?? 'public'),
            ];
        }, $this->repo->allProfiles());
    }
}
