<?php

namespace Modules\Menus\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Modules\Menus\Models\Menu;
use Modules\Menus\Models\MenuItem;
use Modules\Pages\Models\Page;

class MenuAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Menu>
     */
    public function listing()
    {
        return Menu::query()
            ->withCount('items')
            ->orderBy('location')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Menu
    {
        $slug = $this->uniqueMenuSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''));

        /** @var Menu $menu */
        $menu = Menu::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'location' => (string) ($payload['location'] ?? 'primary'),
            'status' => (string) ($payload['status'] ?? 'active'),
        ]);

        return $menu;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Menu $menu, array $payload): Menu
    {
        $slug = $this->uniqueMenuSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $menu->id);

        $menu->fill([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'location' => (string) ($payload['location'] ?? 'primary'),
            'status' => (string) ($payload['status'] ?? 'active'),
        ]);

        $menu->save();

        return $menu;
    }

    public function toggleStatus(Menu $menu): Menu
    {
        $menu->status = $menu->status === 'active' ? 'inactive' : 'active';
        $menu->save();

        return $menu;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createItem(Menu $menu, array $payload): MenuItem
    {
        $type = (string) ($payload['type'] ?? 'url');
        $pageId = $type === 'page' ? (int) ($payload['page_id'] ?? 0) : null;
        $url = $type === 'page' ? $this->pageUrl($pageId) : (string) ($payload['url'] ?? '');

        /** @var MenuItem $item */
        $item = MenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $payload['parent_id'] ? (int) $payload['parent_id'] : null,
            'label' => (string) $payload['label'],
            'url' => $url !== '' ? $url : null,
            'page_id' => $pageId,
            'type' => $type,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'status' => (string) ($payload['status'] ?? 'active'),
        ]);

        return $item;
    }

    public function toggleItemStatus(MenuItem $item): MenuItem
    {
        $item->status = $item->status === 'active' ? 'inactive' : 'active';
        $item->save();

        return $item;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MenuItem>
     */
    public function menuItems(Menu $menu)
    {
        return MenuItem::query()
            ->where('menu_id', $menu->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function frontendTree(string $location = 'primary'): Collection
    {
        /** @var Menu|null $menu */
        $menu = Menu::query()
            ->where('location', $location)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (!$menu) {
            return collect();
        }

        $items = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $byParent = $items->groupBy(fn (MenuItem $item) => $item->parent_id ?: 0);

        $build = function (int $parentId) use (&$build, $byParent): Collection {
            return collect($byParent->get($parentId, []))
                ->map(function (MenuItem $item) use (&$build): array {
                    return [
                        'id' => $item->id,
                        'label' => $item->label,
                        'url' => $item->url ?: '#',
                        'type' => $item->type,
                        'children' => $build($item->id)->values()->all(),
                    ];
                })
                ->values();
        };

        return $build(0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Page>
     */
    public function pagesOptions()
    {
        if (!Schema::hasTable('pages')) {
            return collect();
        }

        return Page::query()
            ->where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
    }

    private function pageUrl(?int $pageId): ?string
    {
        if (!$pageId || !Schema::hasTable('pages')) {
            return null;
        }

        /** @var Page|null $page */
        $page = Page::query()->find($pageId);

        if (!$page) {
            return null;
        }

        return route('frontend.page', ['slug' => $page->slug]);
    }

    private function uniqueMenuSlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'menu';

        $slug = $baseSlug;
        $suffix = 1;

        while ($this->menuSlugExists($slug, $ignoreId)) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }

    private function menuSlugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Menu::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
