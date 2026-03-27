<?php

namespace Modules\Blocks\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Blocks\Models\Block;
use Modules\Blocks\Services\BlockAdminService;

class BlockController extends Controller
{
    public function __construct(private readonly BlockAdminService $blockAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Blocks/Views/index.blade.php'), [
            'currentPage' => 'blocks',
            'blocks' => $this->blockAdminService->listing(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Blocks/Views/create.blade.php'), [
            'currentPage' => 'blocks',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->blockAdminService->create($validated);

        return redirect()->route('admin.blocks.manage')
            ->with('status', 'Bloc cree avec succes.');
    }

    public function edit(Block $block): View
    {
        return view()->file(base_path('modules/Blocks/Views/edit.blade.php'), [
            'currentPage' => 'blocks',
            'block' => $block,
        ]);
    }

    public function update(Request $request, Block $block): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->blockAdminService->update($block, $validated);

        return redirect()->route('admin.blocks.edit', $block)
            ->with('status', 'Bloc mis a jour.');
    }

    public function toggleStatus(Block $block): RedirectResponse
    {
        $updated = $this->blockAdminService->toggleStatus($block);

        return redirect()->route('admin.blocks.manage')
            ->with('status', 'Statut bloc mis a jour (' . $updated->status . ').');
    }
}
