<?php

declare(strict_types=1);

namespace Modules\CatAuthors\services;

use Modules\CatAuthors\repositories\AuthorRepository;

final class AuthorLinkService
{
    public function __construct(private readonly AuthorRepository $repo) {}

    /** Sync the author for an entity (one author per entity). Pass null to unlink. */
    public function syncEntity(string $entityType, int $entityId, ?int $authorProfileId): void
    {
        $this->repo->syncEntityAuthor($entityType, $entityId, $authorProfileId);
    }

    /** Get the author profile id for an entity, null if none */
    public function entityAuthorId(string $entityType, int $entityId): ?int
    {
        return $this->repo->entityAuthorId($entityType, $entityId);
    }

    /** Get a full profile for an entity, null if none */
    public function entityAuthorProfile(string $entityType, int $entityId): ?array
    {
        $id = $this->repo->entityAuthorId($entityType, $entityId);
        if ($id === null) {
            return null;
        }
        return $this->repo->findProfile($id);
    }
}
