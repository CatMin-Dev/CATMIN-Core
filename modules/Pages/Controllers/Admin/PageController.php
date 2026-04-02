<?php

namespace Modules\Pages\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\Editor\WysiwygSanitizer;
use Modules\Pages\Models\Page;
use Modules\Pages\Services\PagesAdminService;

class PageController extends Controller
{
    public function __construct(
        private readonly PagesAdminService $pagesAdminService,
        private readonly WysiwygSanitizer $sanitizer
    )
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $scope = (string) $request->query('scope', 'active');
        if (!in_array($scope, ['active', 'trash', 'all'], true)) {
            $scope = 'active';
        }

        if (in_array($scope, ['trash', 'all'], true) && !catmin_can('module.pages.trash')) {
            abort(403);
        }

        $pages = $this->pagesAdminService
            ->listing($search, 25, $scope)
            ->appends($request->query());

        return view()->file(base_path('modules/Pages/Views/index.blade.php'), [
            'currentPage' => 'content-pages',
            'pages' => $pages,
            'search' => $search,
            'scope' => $scope,
            'trashedCount' => Page::onlyTrashed()->count(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Pages/Views/create.blade.php'), [
            'currentPage' => 'content-pages',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'content'          => ['nullable', 'string'],
            'status'           => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at'     => ['nullable', 'date', 'required_if:status,scheduled'],
            'media_asset_id'   => ['nullable', 'integer', 'min:1'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);

        $this->pagesAdminService->create($validated);

        return redirect()->route('admin.pages.manage')
            ->with('status', 'Page creee avec succes.');
    }

    public function edit(Page $page): View
    {
        return view()->file(base_path('modules/Pages/Views/edit.blade.php'), [
            'currentPage' => 'content-pages',
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'content'          => ['nullable', 'string'],
            'status'           => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at'     => ['nullable', 'date', 'required_if:status,scheduled'],
            'media_asset_id'   => ['nullable', 'integer', 'min:1'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);

        $this->pagesAdminService->update($page, $validated);

        return redirect()->route('admin.pages.manage')
            ->with('status', 'Page mise a jour.');
    }

    public function toggleStatus(Page $page): RedirectResponse
    {
        $updatedPage = $this->pagesAdminService->toggleStatus($page);

        return redirect()->route('admin.pages.manage')
            ->with('status', 'Statut de page mis a jour (' . $updatedPage->status . ').');
    }

    public function preview(Request $request): View
    {
        abort_unless(catmin_can('module.pages.create') || catmin_can('module.pages.edit'), 403);

        $payload = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => ['nullable', 'date'],
        ]);

        $title = (string) ($payload['title'] ?? 'Apercu page');
        $content = $this->sanitizer->sanitize((string) ($payload['content'] ?? ''));
        $renderedContent = inject_blocks($content);

        return view()->file(base_path('modules/Pages/Views/preview.blade.php'), [
            'title' => $title,
            'slug' => (string) ($payload['slug'] ?? ''),
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'publishedAt' => !empty($payload['published_at']) ? (string) $payload['published_at'] : null,
            'renderedContent' => $renderedContent,
        ]);
    }

    public function destroy(int $pageId): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($pageId);

        if ($page->trashed()) {
            return redirect()->route('admin.pages.manage', ['scope' => 'trash'])
                ->with('error', 'Cette page est deja dans la corbeille. Utilisez suppression definitive.');
        }

        $this->pagesAdminService->softDelete($page);

        return redirect()->route('admin.pages.manage')
            ->with('status', 'Page deplacee dans la corbeille.');
    }

    public function restore(int $pageId): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($pageId);

        if (!$page->trashed()) {
            return redirect()->route('admin.pages.manage')
                ->with('error', 'Cette page n\'est pas supprimee.');
        }

        $this->pagesAdminService->restore($page);

        return redirect()->route('admin.pages.manage', ['scope' => 'trash'])
            ->with('status', 'Page restauree.');
    }

    public function forceDelete(int $pageId): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($pageId);

        if (!$page->trashed()) {
            return redirect()->route('admin.pages.manage')
                ->with('error', 'Suppression definitive reservee aux pages en corbeille.');
        }

        $this->pagesAdminService->hardDelete($page);

        return redirect()->route('admin.pages.manage', ['scope' => 'trash'])
            ->with('status', 'Page supprimee definitivement.');
    }

    public function emptyTrash(): RedirectResponse
    {
        $count = $this->pagesAdminService->emptyTrash();

        return redirect()->route('admin.pages.manage', ['scope' => 'trash'])
            ->with('status', $count > 0
                ? sprintf('Corbeille videe: %d page(s) supprimee(s) definitivement.', $count)
                : 'La corbeille est deja vide.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $action = (string) $request->input('bulk_action', '');
        $ids = $request->input('bulk_select', []);

        if (empty($ids) || !is_array($ids)) {
            return redirect()
                ->back()
                ->with('error', 'Veuillez selectionner au moins une page.');
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
            'publish'   => 'module.pages.edit',
            'unpublish' => 'module.pages.edit',
            'trash'     => 'module.pages.trash',
        ];

        $permission = $permissionMap[$action] ?? null;
        if ($permission && !catmin_can($permission)) {
            abort(403);
        }

        $count = 0;
        match ($action) {
            'publish' => $count = $this->pagesAdminService->bulkPublish($ids),
            'unpublish' => $count = $this->pagesAdminService->bulkUnpublish($ids),
            'trash' => $count = $this->pagesAdminService->bulkTrash($ids),
            default => null,
        };

        $messages = [
            'publish' => sprintf('Pages publiees: %d', $count),
            'unpublish' => sprintf('Pages depubliees: %d', $count),
            'trash' => sprintf('Pages envoyees en corbeille: %d', $count),
        ];

        return redirect()
            ->back()
            ->with('status', $messages[$action] ?? 'Action effectuee.');
    }
}
