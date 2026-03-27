<?php

use App\Services\AdminNavigationService;
use App\Services\ModuleManager;
use App\Services\SettingService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Pages\Models\Page;
use Modules\SEO\Models\SeoMeta;

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
