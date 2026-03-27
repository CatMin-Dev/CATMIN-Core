<?php

namespace Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\ShopAdminService;

class ProductController extends Controller
{
    public function __construct(private readonly ShopAdminService $shopAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Shop/Views/index.blade.php'), [
            'currentPage' => 'shop',
            'products' => $this->shopAdminService->listing(request()->only(['status', 'category_id'])),
            'categories' => $this->shopAdminService->activeCategories(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Shop/Views/create.blade.php'), [
            'currentPage' => 'shop',
            'categories' => $this->shopAdminService->activeCategories(),
            'visibilityOptions' => $this->shopAdminService->visibilityOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:shop_products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'visibility' => ['required', Rule::in($this->shopAdminService->visibilityOptions())],
            'manage_stock' => ['nullable', 'boolean'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'product_type' => ['required', Rule::in(['physical', 'digital'])],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:shop_categories,id'],
        ]);

        $validated['manage_stock'] = $request->boolean('manage_stock', true);

        $this->shopAdminService->create($validated);

        return redirect()->route('admin.shop.manage')
            ->with('status', 'Produit cree avec succes.');
    }

    public function edit(Product $product): View
    {
        $product->load('categories');

        return view()->file(base_path('modules/Shop/Views/edit.blade.php'), [
            'currentPage' => 'shop',
            'product' => $product,
            'categories' => $this->shopAdminService->activeCategories(),
            'visibilityOptions' => $this->shopAdminService->visibilityOptions(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('shop_products', 'sku')->ignore($product->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'visibility' => ['required', Rule::in($this->shopAdminService->visibilityOptions())],
            'manage_stock' => ['nullable', 'boolean'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'product_type' => ['required', Rule::in(['physical', 'digital'])],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:shop_categories,id'],
        ]);

        $validated['manage_stock'] = $request->boolean('manage_stock', true);

        $this->shopAdminService->update($product, $validated);

        return redirect()->route('admin.shop.manage')
            ->with('status', 'Produit mis a jour.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $updated = $this->shopAdminService->toggleStatus($product);

        return redirect()->route('admin.shop.manage')
            ->with('status', 'Statut produit mis a jour (' . $updated->status . ').');
    }
}
