<?php

namespace Modules\Articles\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Articles\Models\Tag;
use Modules\Articles\Services\ArticleTaxonomyService;

class ArticleTagController extends Controller
{
    public function __construct(private readonly ArticleTaxonomyService $taxonomyService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Articles/Views/tags/index.blade.php'), [
            'currentPage' => 'content-articles-tags',
            'tags' => $this->taxonomyService->tags(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $this->taxonomyService->createTag($validated);

        return redirect()->route('admin.articles.tags.index')->with('status', 'Tag cree.');
    }

    public function edit(Tag $tag): View
    {
        return view()->file(base_path('modules/Articles/Views/tags/edit.blade.php'), [
            'currentPage' => 'content-articles-tags',
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $this->taxonomyService->updateTag($tag, $validated);

        return redirect()->route('admin.articles.tags.index')->with('status', 'Tag mis a jour.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->taxonomyService->deleteTag($tag);

        return redirect()->route('admin.articles.tags.index')->with('status', 'Tag supprime.');
    }
}