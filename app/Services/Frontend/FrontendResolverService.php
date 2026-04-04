<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Services\SettingService;
use Illuminate\Support\Collection;

/**
 * Centralises the resolution of public frontend context:
 * site settings, navigation menus and SEO payloads.
 *
 * Intentionally lightweight — delegates to existing helpers so that
 * the module guard / schema checks already embedded in those helpers
 * remain the single source of truth.
 */
final class FrontendResolverService
{
    /**
     * Build the site-level context array used in every public view.
     *
     * @param  array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public function context(array $overrides = []): array
    {
        return array_merge(frontend_context(), $overrides);
    }

    /**
     * Resolve the navigation tree for the given menu location.
     *
     * Returns an empty collection when the Menus module is absent or
     * no menu is published at that location.
     *
     * @return Collection<int, array<string,mixed>>
     */
    public function menu(string $location = 'primary'): Collection
    {
        return menu_tree($location);
    }

    /**
     * Build a SEO meta payload for a given target with per-field fallback
     * to global SEO settings and then site settings.
     *
     * @param  array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    public function seo(
        ?string $targetType = null,
        ?int    $targetId   = null,
        array   $overrides  = []
    ): array {
        return seo_meta_payload($targetType, $targetId, $overrides);
    }

    /**
     * Return the configured home page (published).
     */
    public function homePage(): ?\Modules\Pages\Models\Page
    {
        $slug = (string) config('catmin_frontend.home_page_slug', 'home');

        return page_by_slug($slug, true);
    }

    /**
     * Convenience: site name from settings.
     */
    public function siteName(): string
    {
        return (string) SettingService::get('site.name', 'CATMIN');
    }

    /**
     * Convenience: site base URL from settings.
     */
    public function siteUrl(): string
    {
        return (string) SettingService::get('site.url', config('app.url'));
    }
}
