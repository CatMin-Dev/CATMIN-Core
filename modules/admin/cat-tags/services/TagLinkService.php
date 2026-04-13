<?php

declare(strict_types=1);

namespace Modules\CatTags\services;

use Modules\CatTags\repositories\TagsRepository;

final class TagLinkService
{
    public function __construct(private readonly TagsRepository $repo)
    {
    }

    public function syncEntityTags(string $entityType, int $entityId, array $rawTags): array
    {
        $entityType = strtolower(trim($entityType));
        if ($entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => 'entity_type/entity_id invalides'];
        }

        $names = [];
        foreach ($rawTags as $raw) {
            $name = trim((string) $raw);
            if ($name !== '') {
                $names[] = $name;
            }
        }
        $names = array_values(array_unique($names));

        $this->repo->unlinkEntity($entityType, $entityId);

        foreach ($names as $name) {
            $slug = $this->slugify($name);
            if ($slug === '') {
                continue;
            }
            $tag = $this->repo->findTagBySlug($slug);
            $tagId = is_array($tag) ? (int) ($tag['id'] ?? 0) : 0;
            if ($tagId <= 0) {
                $tagId = $this->repo->createTag($name, $slug);
            }
            if ($tagId > 0) {
                $this->repo->linkTag($tagId, $entityType, $entityId);
            }
        }

        $this->repo->refreshUsageCount();

        return ['ok' => true, 'message' => 'Tags synchronises'];
    }

    public function entityTags(string $entityType, int $entityId): array
    {
        return $this->repo->entityTags(strtolower(trim($entityType)), $entityId);
    }

    private function slugify(string $input): string
    {
        $value = mb_strtolower(trim($input));
        $value = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $value) ?? $value;
        $value = preg_replace('/[\s_-]+/u', '-', $value) ?? $value;
        $value = trim((string) $value, '-');
        return mb_substr($value, 0, 160);
    }
}
