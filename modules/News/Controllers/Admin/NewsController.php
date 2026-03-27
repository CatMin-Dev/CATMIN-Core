<?php

namespace Modules\News\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\News\Models\NewsItem;
use Modules\News\Services\NewsAdminService;

class NewsController extends Controller
{
    public function __construct(private readonly NewsAdminService $newsAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/News/Views/index.blade.php'), [
            'currentPage' => 'content-news',
            'items' => $this->newsAdminService->listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/News/Views/create.blade.php'), [
            'currentPage' => 'content-news',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'media_asset_id' => ['nullable', 'integer', 'min:1'],
            'seo_meta_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->newsAdminService->create($validated);

        return redirect()->route('admin.news.manage')
            ->with('status', 'Actualite creee.');
    }

    public function edit(NewsItem $newsItem): View
    {
        return view()->file(base_path('modules/News/Views/edit.blade.php'), [
            'currentPage' => 'content-news',
            'item' => $newsItem,
        ]);
    }

    public function update(Request $request, NewsItem $newsItem): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'media_asset_id' => ['nullable', 'integer', 'min:1'],
            'seo_meta_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->newsAdminService->update($newsItem, $validated);

        return redirect()->route('admin.news.manage')
            ->with('status', 'Actualite mise a jour.');
    }

    public function toggleStatus(NewsItem $newsItem): RedirectResponse
    {
        $updated = $this->newsAdminService->toggleStatus($newsItem);

        return redirect()->route('admin.news.manage')
            ->with('status', 'Statut actualite: ' . $updated->status . '.');
    }
}
