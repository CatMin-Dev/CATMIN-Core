<?php

namespace Modules\Pages\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Pages\Models\Page;
use Modules\Pages\Services\PagesAdminService;

class PageController extends Controller
{
    public function __construct(private readonly PagesAdminService $pagesAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Pages/Views/index.blade.php'), [
            'currentPage' => 'content-pages',
            'pages' => $this->pagesAdminService->listing(),
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
            'status'           => ['required', Rule::in(['draft', 'published'])],
            'published_at'     => ['nullable', 'date'],
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
            'status'           => ['required', Rule::in(['draft', 'published'])],
            'published_at'     => ['nullable', 'date'],
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
}
