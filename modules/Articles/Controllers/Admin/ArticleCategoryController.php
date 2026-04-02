<?php

namespace Modules\Articles\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Articles\Models\ArticleCategory;
use Modules\Articles\Services\ArticleTaxonomyService;

class ArticleCategoryController extends Controller
{
    public function __construct(private readonly ArticleTaxonomyService $taxonomyService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Articles/Views/categories/index.blade.php'), [
            'currentPage' => 'content-articles-categories',
            'categories' => $this->taxonomyService->categories(),
            'parents' => $this->taxonomyService->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:article_categories,id'],
        ]);

        $this->taxonomyService->createCategory($validated);

        return redirect()->route('admin.articles.categories.index')->with('status', 'Categorie article creee.');
    }

    public function edit(ArticleCategory $category): View
    {
        return view()->file(base_path('modules/Articles/Views/categories/edit.blade.php'), [
            'currentPage' => 'content-articles-categories',
            'category' => $category,
            'parents' => $this->taxonomyService->categories()->where('id', '!=', $category->id)->values(),
        ]);
    }

    public function update(Request $request, ArticleCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::notIn([$category->id]), 'exists:article_categories,id'],
        ]);

        $this->taxonomyService->updateCategory($category, $validated);

        return redirect()->route('admin.articles.categories.index')->with('status', 'Categorie article mise a jour.');
    }

    public function destroy(ArticleCategory $category): RedirectResponse
    {
        if (!$this->taxonomyService->deleteCategory($category)) {
            return redirect()->route('admin.articles.categories.index')->with('error', 'Suppression impossible: categorie parente ou utilisee par des articles.');
        }

        return redirect()->route('admin.articles.categories.index')->with('status', 'Categorie article supprimee.');
    }
}