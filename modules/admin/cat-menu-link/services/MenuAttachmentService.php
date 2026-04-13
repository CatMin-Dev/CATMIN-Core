<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\services;

use Modules\CatMenuLink\repositories\MenuLinkRepository;

final class MenuAttachmentService
{
    public function __construct(
        private readonly MenuLinkRepository $repository,
        private readonly MenuLinkValidationService $validator
    ) {
    }

    public function attach(array $payload): array
    {
        $payload['menu_key'] = $this->validator->normalizeMenuKey((string) ($payload['menu_key'] ?? 'main_nav'));
        $payload['link_type'] = $this->validator->normalizeLinkType((string) ($payload['link_type'] ?? 'entity_link'));
        $payload['label_override'] = $this->validator->sanitizeLabel((string) ($payload['label_override'] ?? ''));
        return $this->repository->upsertEntityLink($payload);
    }
}
