<?php

namespace Modules\Media\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Services\MediaAdminService;

class MediaController extends Controller
{
    public function __construct(private readonly MediaAdminService $mediaAdminService)
    {
    }

    public function index(): View
    {
        $assets = $this->mediaAdminService->listing();

        return view()->file(base_path('modules/Media/Views/index.blade.php'), [
            'currentPage' => 'content-media',
            'assets' => $assets,
            'mediaService' => $this->mediaAdminService,
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Media/Views/create.blade.php'), [
            'currentPage' => 'content-media',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->mediaAdminService->create(
            $request->file('file'),
            $validated['alt_text'] ?? null,
            $validated['caption'] ?? null,
        );

        return redirect()->route('admin.media.manage')
            ->with('status', 'Fichier media ajoute.');
    }

    public function edit(MediaAsset $asset): View
    {
        return view()->file(base_path('modules/Media/Views/edit.blade.php'), [
            'currentPage' => 'content-media',
            'asset' => $asset,
            'previewUrl' => $this->mediaAdminService->previewUrl($asset),
            'mediaService' => $this->mediaAdminService,
        ]);
    }

    public function update(Request $request, MediaAsset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->mediaAdminService->update($asset, $validated);

        return redirect()->route('admin.media.manage')
            ->with('status', 'Metadonnees media mises a jour.');
    }

    public function destroy(MediaAsset $asset): RedirectResponse
    {
        $this->mediaAdminService->destroy($asset);

        return redirect()->route('admin.media.manage')
            ->with('status', 'Fichier media supprime.');
    }
}
