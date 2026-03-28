<?php

namespace Modules\Articles\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Articles\Models\Article;
use Modules\Articles\Services\ArticleAdminService;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleAdminService $articleAdminService)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view()->file(base_path('modules/Articles/Views/index.blade.php'), [
            'currentPage' => 'content-articles',
            'items' => $this->articleAdminService->listing($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Articles/Views/create.blade.php'), [
            'currentPage' => 'content-articles',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:1000'],
            'content'          => ['nullable', 'string'],
            'content_type'     => ['required', Rule::in(['article', 'news'])],
            'status'           => ['required', Rule::in(['draft', 'published'])],
            'published_at'     => ['nullable', 'date'],
            'media_asset_id'   => ['nullable', 'integer', 'min:1'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);

        $this->articleAdminService->create($validated);

        return redirect()->route('admin.articles.manage')
            ->with('status', 'Article cree.');
    }

    public function edit(Article $article): View
    {
        return view()->file(base_path('modules/Articles/Views/edit.blade.php'), [
            'currentPage' => 'content-articles',
            'item' => $article,
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:1000'],
            'content'          => ['nullable', 'string'],
            'content_type'     => ['required', Rule::in(['article', 'news'])],
            'status'           => ['required', Rule::in(['draft', 'published'])],
            'published_at'     => ['nullable', 'date'],
            'media_asset_id'   => ['nullable', 'integer', 'min:1'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);

        $this->articleAdminService->update($article, $validated);

        return redirect()->route('admin.articles.manage')
            ->with('status', 'Article mis a jour.');
    }

    public function toggleStatus(Article $article): RedirectResponse
    {
        $updated = $this->articleAdminService->toggleStatus($article);

        return redirect()->route('admin.articles.manage')
            ->with('status', 'Statut article: ' . $updated->status . '.');
    }
}
