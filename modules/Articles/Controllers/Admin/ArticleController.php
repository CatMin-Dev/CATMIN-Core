<?php

namespace Modules\Articles\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\Editor\WysiwygSanitizer;
use Modules\Articles\Models\Article;
use Modules\Articles\Services\ArticleAdminService;
use Modules\Articles\Services\ArticleTaxonomyService;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleAdminService $articleAdminService,
        private readonly ArticleTaxonomyService $articleTaxonomyService,
        private readonly WysiwygSanitizer $sanitizer
    )
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $scope = (string) $request->query('scope', 'active');
        $categoryId = $request->filled('category_id') ? (int) $request->query('category_id') : null;
        $tagId = $request->filled('tag_id') ? (int) $request->query('tag_id') : null;
        if (!in_array($scope, ['active', 'trash', 'all'], true)) {
            $scope = 'active';
        }

        if (in_array($scope, ['trash', 'all'], true) && !catmin_can('module.articles.trash')) {
            abort(403);
        }

        $items = $this->articleAdminService
            ->listing($search, 25, $scope, $categoryId, $tagId)
            ->appends($request->query());

        return view()->file(base_path('modules/Articles/Views/index.blade.php'), [
            'currentPage' => 'content-articles',
            'items' => $items,
            'search' => $search,
            'scope' => $scope,
            'selectedCategoryId' => $categoryId,
            'selectedTagId' => $tagId,
            'categories' => $this->articleTaxonomyService->categoriesForSelect(),
            'tags' => $this->articleTaxonomyService->tags(),
            'trashedCount' => Article::onlyTrashed()->count(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Articles/Views/create.blade.php'), [
            'currentPage' => 'content-articles',
            'categories' => $this->articleTaxonomyService->categoriesForSelect(),
            'tags' => $this->articleTaxonomyService->tags(),
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
            'article_category_id' => ['nullable', 'integer', 'exists:article_categories,id'],
            'tag_ids'          => ['nullable', 'array'],
            'tag_ids.*'        => ['integer', 'exists:tags,id'],
            'status'           => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at'     => ['nullable', 'date', 'required_if:status,scheduled'],
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
        $article->loadMissing(['category', 'tags']);

        return view()->file(base_path('modules/Articles/Views/edit.blade.php'), [
            'currentPage' => 'content-articles',
            'item' => $article,
            'categories' => $this->articleTaxonomyService->categoriesForSelect(),
            'tags' => $this->articleTaxonomyService->tags(),
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
            'article_category_id' => ['nullable', 'integer', 'exists:article_categories,id'],
            'tag_ids'          => ['nullable', 'array'],
            'tag_ids.*'        => ['integer', 'exists:tags,id'],
            'status'           => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at'     => ['nullable', 'date', 'required_if:status,scheduled'],
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

    public function preview(Request $request): View
    {
        abort_unless(catmin_can('module.articles.create') || catmin_can('module.articles.edit'), 403);

        $payload = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'published'])],
            'content_type' => ['nullable', Rule::in(['article', 'news'])],
            'published_at' => ['nullable', 'date'],
        ]);

        $title = (string) ($payload['title'] ?? 'Apercu article');
        $content = $this->sanitizer->sanitize((string) ($payload['content'] ?? ''));
        $renderedContent = inject_blocks($content);

        return view()->file(base_path('modules/Articles/Views/preview.blade.php'), [
            'title' => $title,
            'slug' => (string) ($payload['slug'] ?? ''),
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'contentType' => (string) ($payload['content_type'] ?? 'article'),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'publishedAt' => !empty($payload['published_at']) ? (string) $payload['published_at'] : null,
            'renderedContent' => $renderedContent,
        ]);
    }

    public function destroy(int $articleId): RedirectResponse
    {
        $item = Article::withTrashed()->findOrFail($articleId);

        if ($item->trashed()) {
            return redirect()->route('admin.articles.manage', ['scope' => 'trash'])
                ->with('error', 'Cet article est deja dans la corbeille. Utilisez suppression definitive.');
        }

        $this->articleAdminService->softDelete($item);

        return redirect()->route('admin.articles.manage')
            ->with('status', 'Article deplace dans la corbeille.');
    }

    public function restore(int $articleId): RedirectResponse
    {
        $item = Article::withTrashed()->findOrFail($articleId);

        if (!$item->trashed()) {
            return redirect()->route('admin.articles.manage')
                ->with('error', 'Cet article n\'est pas supprime.');
        }

        $this->articleAdminService->restore($item);

        return redirect()->route('admin.articles.manage', ['scope' => 'trash'])
            ->with('status', 'Article restaure.');
    }

    public function forceDelete(int $articleId): RedirectResponse
    {
        $item = Article::withTrashed()->findOrFail($articleId);

        if (!$item->trashed()) {
            return redirect()->route('admin.articles.manage')
                ->with('error', 'Suppression definitive reservee aux articles en corbeille.');
        }

        $this->articleAdminService->hardDelete($item);

        return redirect()->route('admin.articles.manage', ['scope' => 'trash'])
            ->with('status', 'Article supprime definitivement.');
    }

    public function emptyTrash(): RedirectResponse
    {
        $count = $this->articleAdminService->emptyTrash();

        return redirect()->route('admin.articles.manage', ['scope' => 'trash'])
            ->with('status', $count > 0
                ? sprintf('Corbeille articles videe: %d article(s) supprime(s) definitivement.', $count)
                : 'La corbeille articles est deja vide.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $action = (string) $request->input('bulk_action', '');
        $ids = $request->input('bulk_select', []);

        if (empty($ids) || !is_array($ids)) {
            return redirect()
                ->back()
                ->with('error', 'Veuillez selectionner au moins un article.');
        }

        // Sanitize and validate IDs
        $ids = collect($ids)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return redirect()
                ->back()
                ->with('error', 'Identifiants invalides.');
        }

        // Check permission based on action
        $permissionMap = [
            'publish'   => 'module.articles.edit',
            'unpublish' => 'module.articles.edit',
            'trash'     => 'module.articles.trash',
        ];

        $permission = $permissionMap[$action] ?? null;
        if ($permission && !catmin_can($permission)) {
            abort(403);
        }

        $count = 0;
        match ($action) {
            'publish' => $count = $this->articleAdminService->bulkPublish($ids),
            'unpublish' => $count = $this->articleAdminService->bulkUnpublish($ids),
            'trash' => $count = $this->articleAdminService->bulkTrash($ids),
            default => null,
        };

        $messages = [
            'publish' => sprintf('Articles publies: %d', $count),
            'unpublish' => sprintf('Articles depublies: %d', $count),
            'trash' => sprintf('Articles envoyes en corbeille: %d', $count),
        ];

        return redirect()
            ->back()
            ->with('status', $messages[$action] ?? 'Action effectuee.');
    }
}
