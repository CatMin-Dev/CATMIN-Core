<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\services;

use Modules\CatMenuLink\repositories\MenuLinkRepository;

final class MenuLinkService
{
    public function __construct(
        private readonly MenuLinkRepository $repository,
        private readonly MenuAttachmentService $attachment,
        private readonly MenuTreeService $tree,
        private readonly BreadcrumbSeedService $breadcrumbs
    ) {
    }

    public function dashboard(string $menuKey = 'main_nav'): array
    {
        $menuKey = trim($menuKey) !== '' ? trim($menuKey) : 'main_nav';
        $items = $this->repository->listItems($menuKey);

        return [
            'stats' => $this->repository->stats(),
            'menus' => $this->repository->listMenus(),
            'menu_key' => $menuKey,
            'items' => $items,
            'tree' => $this->tree->tree($items),
            'all_items' => $this->repository->listAll(400),
            'breadcrumb_snippet' => $this->breadcrumbs->snippet($menuKey),
        ];
    }

    public function attach(array $payload): array
    {
        return $this->attachment->attach($payload);
    }

    public function reorder(string $menuKey, array $rows): array
    {
        return $this->repository->reorder($menuKey, $rows);
    }

    public function delete(int $id): array
    {
        return $this->repository->delete($id);
    }
}
