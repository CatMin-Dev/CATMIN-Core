<?php

namespace Addons\CatminShop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Addons\CatminShop\Models\Category;
use Addons\CatminShop\Services\ShopAdminService;

class CategoryController extends Controller
{
    public function __construct(private readonly ShopAdminService $shopAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('addons/catmin-shop/Views/categories/index.blade.php'), [
            'currentPage' => 'shop',
            'categories' => $this->shopAdminService->categories(),
            'parents' => $this->shopAdminService->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:shop_categories,id'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->shopAdminService->createCategory($validated);

        return redirect()->route('admin.shop.categories.index')->with('status', 'Categorie creee.');
    }

    public function edit(Category $category): View
    {
        return view()->file(base_path('addons/catmin-shop/Views/categories/edit.blade.php'), [
            'currentPage' => 'shop',
            'category' => $category,
            'parents' => $this->shopAdminService->categories()->where('id', '!=', $category->id)->values(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::notIn([$category->id]), 'exists:shop_categories,id'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $this->shopAdminService->updateCategory($category, $validated);

        return redirect()->route('admin.shop.categories.index')->with('status', 'Categorie mise a jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if (!$this->shopAdminService->deleteCategory($category)) {
            return redirect()->route('admin.shop.categories.index')->with('error', 'Suppression impossible: categorie parente ou liee a des produits.');
        }

        return redirect()->route('admin.shop.categories.index')->with('status', 'Categorie supprimee.');
    }
}
