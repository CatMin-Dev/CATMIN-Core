<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

use Modules\CatMediaLink\repositories\MediaLinkRepository;

final class MediaUsageService
{
    public function __construct(private readonly MediaLinkRepository $repository)
    {
    }

    public function latest(int $limit = 120): array
    {
        return $this->repository->listLatestUsages($limit);
    }

    public function byMedia(int $mediaId): array
    {
        return $this->repository->listUsage($mediaId);
    }
}
