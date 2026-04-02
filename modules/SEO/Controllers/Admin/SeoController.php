<?php

namespace Modules\SEO\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SEO\Models\SeoMeta;
use Modules\SEO\Services\SitemapService;
use Modules\SEO\Services\SeoAdminService;

class SeoController extends Controller
{
    public function __construct(private readonly SeoAdminService $seoAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/SEO/Views/index.blade.php'), [
            'currentPage' => 'modules',
            'records' => $this->seoAdminService->listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/SEO/Views/create.blade.php'), [
            'currentPage' => 'modules',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_type' => ['nullable', 'string', 'max:120'],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_robots' => ['nullable', 'string', 'max:60'],
            'canonical_url' => ['nullable', 'string', 'url', 'max:255'],
            'slug_override' => ['nullable', 'string', 'max:255'],
        ]);

        $this->seoAdminService->create($validated);

        return redirect()->route('admin.seo.manage')
            ->with('status', 'Entree SEO ajoutee.');
    }

    public function edit(SeoMeta $seoMeta): View
    {
        return view()->file(base_path('modules/SEO/Views/edit.blade.php'), [
            'currentPage' => 'modules',
            'seoMeta' => $seoMeta,
        ]);
    }

    public function update(Request $request, SeoMeta $seoMeta): RedirectResponse
    {
        $validated = $request->validate([
            'target_type' => ['nullable', 'string', 'max:120'],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_robots' => ['nullable', 'string', 'max:60'],
            'canonical_url' => ['nullable', 'string', 'url', 'max:255'],
            'slug_override' => ['nullable', 'string', 'max:255'],
        ]);

        $this->seoAdminService->update($seoMeta, $validated);

        return redirect()->route('admin.seo.manage')
            ->with('status', 'Entree SEO mise a jour.');
    }

    public function refreshSitemap(SitemapService $service): RedirectResponse
    {
        $service->refresh();

        return redirect()->route('admin.seo.manage')
            ->with('status', 'Sitemap regenere avec succes.');
    }
}
