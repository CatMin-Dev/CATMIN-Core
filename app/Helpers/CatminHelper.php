<?php

use App\Services\AdminNavigationService;
use App\Services\ModuleManager;
use App\Services\RbacPermissionService;
use App\Services\SettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Articles\Models\Article;
use Modules\Blocks\Services\BlockAdminService;
use Modules\Media\Models\MediaAsset;
use Modules\Menus\Services\MenuAdminService;
use Modules\Pages\Models\Page;
use Modules\SEO\Models\SeoMeta;

if (!function_exists('catmin_can')) {
    /**
     * Check if the current admin session has the given RBAC permission.
     * Returns true for super-admin (wildcard '*') and when RBAC is disabled.
     */
    function catmin_can(string $permission): bool
    {
        return RbacPermissionService::allows(request(), $permission);
    }
}

if (!function_exists('setting')) {
    /**
     * Read a CATMIN setting with optional fallback value.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return SettingService::get($key, $default);
    }
}

if (!function_exists('module_enabled')) {
    function module_enabled(string $slug): bool
    {
        return ModuleManager::isEnabled($slug);
    }
}

if (!function_exists('module_info')) {
    function module_info(string $slug, ?string $property = null, mixed $default = null): mixed
    {
        $module = ModuleManager::find($slug);

        if (!$module) {
            return $default;
        }

        if ($property === null) {
            return $module;
        }

        return $module->{$property} ?? $default;
    }
}

if (!function_exists('admin_url')) {
    /**
     * Generate an admin URL from a route name without the admin. prefix.
     */
    function admin_url(string $name, array $parameters = []): string
    {
        return admin_route($name, $parameters);
    }
}

if (!function_exists('admin_url_safe')) {
    /**
     * Generate an admin URL with route existence check and path fallback.
     */
    function admin_url_safe(string $name, array $parameters = [], ?string $fallbackPath = null): string
    {
        $routeName = 'admin.' . $name;

        if (Route::has($routeName)) {
            return route($routeName, $parameters);
        }

        return admin_path($fallbackPath ?? $name);
    }
}

if (!function_exists('catmin_navigation')) {
    function catmin_navigation(?string $currentPage = null): array
    {
        return AdminNavigationService::sections($currentPage);
    }
}

if (!function_exists('catmin_theme')) {
    /**
     * Return the active CATMIN admin theme.
     */
    function catmin_theme(string $default = 'catmin-light'): string
    {
        return (string) setting('admin.theme', $default);
    }
}

if (!function_exists('page_by_slug')) {
    /**
     * Retrieve a page by slug from the Pages module.
     */
    function page_by_slug(string $slug, bool $onlyPublished = true): ?Page
    {
        $normalizedSlug = trim($slug);

        if ($normalizedSlug === '') {
            return null;
        }

        if (!ModuleManager::isEnabled('pages')) {
            return null;
        }

        if (!Schema::hasTable('pages')) {
            return null;
        }

        $query = Page::query()->where('slug', $normalizedSlug);

        if ($onlyPublished) {
            $query->where('status', 'published');
        }

        return $query->first();
    }
}

if (!function_exists('frontend_context')) {
    /**
     * Expose a compact frontend context payload for Blade or plain PHP usage.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    function frontend_context(array $overrides = []): array
    {
        $base = [
            'site_name' => (string) setting('site.name', 'CATMIN'),
            'site_url' => (string) setting('site.url', config('app.url')),
            'frontend_enabled' => (bool) setting('site.frontend_enabled', config('catmin.frontend.enabled', true)),
            'admin_login_url' => admin_url_safe('login', [], 'login'),
            'admin_home_url' => admin_url_safe('index', [], ''),
            'enabled_modules' => ModuleManager::enabled()->pluck('slug')->values()->all(),
        ];

        return array_merge($base, $overrides);
    }
}

if (!function_exists('seo_for')) {
    /**
     * Retrieve SEO metadata by target type and target id.
     */
    function seo_for(string $targetType, int $targetId): ?SeoMeta
    {
        if (!ModuleManager::isEnabled('seo')) {
            return null;
        }

        if (!Schema::hasTable('seo_meta')) {
            return null;
        }

        return SeoMeta::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();
    }
}

if (!function_exists('seo_meta_payload')) {
    /**
     * Build a lightweight SEO payload with target -> global -> settings fallback.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    function seo_meta_payload(?string $targetType = null, ?int $targetId = null, array $overrides = []): array
    {
        $siteName = (string) setting('site.name', 'CATMIN');
        $defaultDescription = (string) setting('site.description', 'CATMIN');
        $defaultRobots = (string) setting('seo.meta_robots', 'index,follow');

        $targetSeo = null;
        if ($targetType !== null && $targetId !== null && $targetId > 0) {
            $targetSeo = seo_for($targetType, $targetId);
        }

        $globalSeo = null;
        if (ModuleManager::isEnabled('seo') && Schema::hasTable('seo_meta')) {
            $globalSeo = SeoMeta::query()
                ->where(function ($query): void {
                    $query->whereNull('target_type')
                        ->whereNull('target_id');
                })
                ->orWhere(function ($query): void {
                    $query->where('target_type', 'global')
                        ->whereNull('target_id');
                })
                ->orderByDesc('id')
                ->first();
        }

        $title = (string) ($overrides['title']
            ?? $targetSeo?->meta_title
            ?? $globalSeo?->meta_title
            ?? $siteName);
        $description = (string) ($overrides['description']
            ?? $targetSeo?->meta_description
            ?? $globalSeo?->meta_description
            ?? $defaultDescription);
        $robots = (string) ($overrides['robots']
            ?? $targetSeo?->meta_robots
            ?? $globalSeo?->meta_robots
            ?? $defaultRobots);
        $canonical = (string) ($overrides['canonical']
            ?? $targetSeo?->canonical_url
            ?? $globalSeo?->canonical_url
            ?? url()->current());
        $ogTitle = (string) ($overrides['og_title'] ?? $title);
        $ogDescription = (string) ($overrides['og_description'] ?? $description);
        $ogType = (string) ($overrides['og_type'] ?? ($targetType !== null ? 'article' : 'website'));
        $ogImage = $overrides['og_image'] ?? null;

        return [
            'title' => $title,
            'description' => $description,
            'robots' => $robots,
            'canonical' => $canonical,
            'og' => [
                'title' => $ogTitle,
                'description' => $ogDescription,
                'type' => $ogType,
                'url' => $canonical,
                'site_name' => $siteName,
                'image' => $ogImage,
            ],
        ];
    }
}

if (!function_exists('editorial_items')) {
    /**
     * Retrieve editorial items from Articles module with simple filtering.
     *
     * @return Collection<int, Article>
     */
    function editorial_items(
        string $type = 'article',
        int $limit = 10,
        string $orderBy = 'published_at',
        string $direction = 'desc',
        bool $onlyPublished = true
    ): Collection {
        if (!ModuleManager::isEnabled('articles') || !Schema::hasTable('articles')) {
            return collect();
        }

        $safeLimit = max(1, min($limit, 100));
        $safeDirection = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $allowedOrderBy = ['published_at', 'created_at', 'updated_at', 'title'];
        $safeOrderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'published_at';

        $query = Article::query()->where('content_type', $type);

        if ($onlyPublished) {
            $query->where('status', 'published');
        }

        return $query
            ->orderBy($safeOrderBy, $safeDirection)
            ->limit($safeLimit)
            ->get();
    }
}

if (!function_exists('news_items')) {
    /**
     * Retrieve a frontend-ready list of news items.
     *
     * @return Collection<int, Article>
     */
    function news_items(int $limit = 5, string $orderBy = 'published_at', string $direction = 'desc'): Collection
    {
        return editorial_items('news', $limit, $orderBy, $direction, true);
    }
}

if (!function_exists('blog_posts')) {
    /**
     * Retrieve a frontend-ready list of blog posts.
     *
     * @return Collection<int, Article>
     */
    function blog_posts(int $limit = 5, string $orderBy = 'published_at', string $direction = 'desc'): Collection
    {
        return editorial_items('article', $limit, $orderBy, $direction, true);
    }
}

if (!function_exists('media_asset')) {
    /**
     * Retrieve a media asset by id.
     */
    function media_asset(?int $id): ?MediaAsset
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        if (!ModuleManager::isEnabled('media') || !Schema::hasTable('media_assets')) {
            return null;
        }

        return MediaAsset::query()->find($id);
    }
}

if (!function_exists('media_url')) {
    /**
     * Resolve a public URL for a media asset id or model.
     */
    function media_url(int|MediaAsset|null $media, ?string $fallback = null): ?string
    {
        $asset = $media instanceof MediaAsset ? $media : media_asset($media);

        if (!$asset) {
            return $fallback;
        }

        try {
            return Storage::url($asset->path);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}

if (!function_exists('menu_tree')) {
    /**
     * Resolve active frontend menu tree by location.
     *
     * @return Collection<int, array<string, mixed>>
     */
    function menu_tree(string $location = 'primary'): Collection
    {
        if (!ModuleManager::isEnabled('menus')) {
            return collect();
        }

        if (!Schema::hasTable('menus') || !Schema::hasTable('menu_items')) {
            return collect();
        }

        /** @var MenuAdminService $service */
        $service = app(MenuAdminService::class);

        return $service->frontendTree($location);
    }
}

if (!function_exists('block_content')) {
    /**
     * Resolve active block content by slug.
     */
    function block_content(string $slug, string $fallback = ''): string
    {
        if (!ModuleManager::isEnabled('blocks')) {
            return $fallback;
        }

        if (!Schema::hasTable('blocks')) {
            return $fallback;
        }

        /** @var BlockAdminService $service */
        $service = app(BlockAdminService::class);
        $block = $service->findActiveBySlug(trim($slug));

        return $block?->content !== null ? (string) $block->content : $fallback;
    }
}

if (!function_exists('inject_blocks')) {
    /**
     * Replace placeholders like {{ block:hero }} in content.
     */
    function inject_blocks(string $content): string
    {
        return (string) preg_replace_callback('/\{\{\s*block:([a-zA-Z0-9\-_\.]+)\s*\}\}/', function (array $matches): string {
            $slug = (string) ($matches[1] ?? '');

            return block_content($slug, '');
        }, $content);
    }
}

if (!function_exists('menu_items')) {
    /**
     * Frontend convenience helper returning menu tree as plain array.
     *
     * @return array<int, array<string, mixed>>
     */
    function menu_items(string $location = 'primary'): array
    {
        return menu_tree($location)->values()->all();
    }
}

if (!function_exists('news_cards')) {
    /**
     * Frontend-ready payload for news listing snippets.
     *
     * @return Collection<int, array<string, mixed>>
     */
    function news_cards(int $limit = 5): Collection
    {
        return news_items($limit)
            ->map(fn (Article $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'excerpt' => (string) ($item->excerpt ?? ''),
                'published_at' => $item->published_at,
                'media_url' => media_url($item->media_asset_id),
            ])
            ->values();
    }
}

if (!function_exists('blog_cards')) {
    /**
     * Frontend-ready payload for blog listing snippets.
     *
     * @return Collection<int, array<string, mixed>>
     */
    function blog_cards(int $limit = 5): Collection
    {
        return blog_posts($limit)
            ->map(fn (Article $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'excerpt' => (string) ($item->excerpt ?? ''),
                'published_at' => $item->published_at,
                'media_url' => media_url($item->media_asset_id),
            ])
            ->values();
    }
}

if (!function_exists('render_block')) {
    /**
     * Resolve and render a block by slug with optional fallback.
     */
    function render_block(string $slug, string $fallback = ''): string
    {
        return inject_blocks(block_content($slug, $fallback));
    }
}

if (!function_exists('media_img_tag')) {
    /**
     * Generate a lightweight IMG tag from a media id or model.
     *
     * @param array<string, string> $attributes
     */
    function media_img_tag(int|MediaAsset|null $media, array $attributes = [], ?string $fallbackUrl = null): string
    {
        $url = media_url($media, $fallbackUrl);
        if ($url === null || $url === '') {
            return '';
        }

        $attrs = [
            'src' => $url,
            'alt' => (string) ($attributes['alt'] ?? ''),
            'loading' => (string) ($attributes['loading'] ?? 'lazy'),
        ];

        foreach ($attributes as $key => $value) {
            if ($key !== '' && !array_key_exists($key, $attrs)) {
                $attrs[$key] = (string) $value;
            }
        }

        $parts = collect($attrs)
            ->map(fn ($value, $key) => $key . '="' . e((string) $value) . '"')
            ->implode(' ');

        return '<img ' . $parts . '>';
    }
}

if (!function_exists('setting_text')) {
    /**
     * Read setting as plain string for frontend snippets.
     */
    function setting_text(string $key, string $default = ''): string
    {
        return (string) setting($key, $default);
    }
}
