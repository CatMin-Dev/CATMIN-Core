<?php

namespace Modules\Blog\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Services\BlogAdminService;

class BlogController extends Controller
{
    public function __construct(private readonly BlogAdminService $blogAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Blog/Views/index.blade.php'), [
            'currentPage' => 'content-blog',
            'items' => $this->blogAdminService->listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Blog/Views/create.blade.php'), [
            'currentPage' => 'content-blog',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'media_asset_id' => ['nullable', 'integer', 'min:1'],
            'seo_meta_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->blogAdminService->create($validated);

        return redirect()->route('admin.blog.manage')
            ->with('status', 'Article blog cree.');
    }

    public function edit(BlogPost $blogPost): View
    {
        return view()->file(base_path('modules/Blog/Views/edit.blade.php'), [
            'currentPage' => 'content-blog',
            'item' => $blogPost,
        ]);
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'media_asset_id' => ['nullable', 'integer', 'min:1'],
            'seo_meta_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->blogAdminService->update($blogPost, $validated);

        return redirect()->route('admin.blog.manage')
            ->with('status', 'Article blog mis a jour.');
    }

    public function toggleStatus(BlogPost $blogPost): RedirectResponse
    {
        $updated = $this->blogAdminService->toggleStatus($blogPost);

        return redirect()->route('admin.blog.manage')
            ->with('status', 'Statut blog: ' . $updated->status . '.');
    }
}
