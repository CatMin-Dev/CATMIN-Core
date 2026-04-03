<?php

namespace Addons\CatminMap\Controllers\Admin;

use Addons\CatminMap\Models\GeoCategory;
use Addons\CatminMap\Services\GeoAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeoCategoryController extends Controller
{
    public function __construct(private readonly GeoAdminService $service) {}

    public function index(): View
    {
        $categories = $this->service->categories();

        return view()->file(
            base_path('addons/catmin-map/Views/categories/index.blade.php'),
            compact('categories')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => 'required|string|max:120',
            'color' => 'nullable|string|max:32',
            'icon'  => 'nullable|string|max:64',
        ]);

        $this->service->createCategory($request->all());

        return redirect()->route('admin.map.categories.index')
            ->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, GeoCategory $geoCategory): RedirectResponse
    {
        $request->validate([
            'name'  => 'required|string|max:120',
            'color' => 'nullable|string|max:32',
            'icon'  => 'nullable|string|max:64',
        ]);

        $this->service->updateCategory($geoCategory, $request->all());

        return redirect()->route('admin.map.categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(GeoCategory $geoCategory): RedirectResponse
    {
        $this->service->deleteCategory($geoCategory);

        return redirect()->route('admin.map.categories.index')
            ->with('success', 'Catégorie supprimée.');
    }
}
