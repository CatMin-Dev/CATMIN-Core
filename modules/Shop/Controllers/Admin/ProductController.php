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
            'products' => $this->shopAdminService->listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Shop/Views/create.blade.php'), [
            'currentPage' => 'shop',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->shopAdminService->create($validated);

        return redirect()->route('admin.shop.manage')
            ->with('status', 'Produit cree avec succes.');
    }

    public function edit(Product $product): View
    {
        return view()->file(base_path('modules/Shop/Views/edit.blade.php'), [
            'currentPage' => 'shop',
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

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
