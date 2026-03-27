<?php

namespace Modules\Menus\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Menus\Models\Menu;
use Modules\Menus\Models\MenuItem;
use Modules\Menus\Services\MenuAdminService;

class MenuController extends Controller
{
    public function __construct(private readonly MenuAdminService $menuAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Menus/Views/index.blade.php'), [
            'currentPage' => 'menus',
            'menus' => $this->menuAdminService->listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Menus/Views/create.blade.php'), [
            'currentPage' => 'menus',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:80'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->menuAdminService->create($validated);

        return redirect()->route('admin.menus.manage')
            ->with('status', 'Menu cree avec succes.');
    }

    public function edit(Menu $menu): View
    {
        return view()->file(base_path('modules/Menus/Views/edit.blade.php'), [
            'currentPage' => 'menus',
            'menu' => $menu,
            'items' => $this->menuAdminService->menuItems($menu),
            'pagesOptions' => $this->menuAdminService->pagesOptions(),
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:80'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->menuAdminService->update($menu, $validated);

        return redirect()->route('admin.menus.edit', $menu)
            ->with('status', 'Menu mis a jour.');
    }

    public function toggleStatus(Menu $menu): RedirectResponse
    {
        $updated = $this->menuAdminService->toggleStatus($menu);

        return redirect()->route('admin.menus.manage')
            ->with('status', 'Statut menu mis a jour (' . $updated->status . ').');
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['url', 'page'])],
            'url' => ['nullable', 'string', 'max:2048'],
            'page_id' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->menuAdminService->createItem($menu, $validated);

        return redirect()->route('admin.menus.edit', $menu)
            ->with('status', 'Item de menu ajoute.');
    }

    public function toggleItemStatus(Menu $menu, MenuItem $item): RedirectResponse
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }

        $updated = $this->menuAdminService->toggleItemStatus($item);

        return redirect()->route('admin.menus.edit', $menu)
            ->with('status', 'Statut item mis a jour (' . $updated->status . ').');
    }
}
