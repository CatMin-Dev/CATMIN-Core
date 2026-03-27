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

    public function index(Request $request): View
    {
        $folder = (string) $request->query('folder', '');
        $assets = $this->mediaAdminService->listing($folder !== '' ? $folder : null);
        $folders = $this->mediaAdminService->folders();

        return view()->file(base_path('modules/Media/Views/index.blade.php'), [
            'currentPage' => 'content-media',
            'assets' => $assets,
            'mediaService' => $this->mediaAdminService,
            'folders' => $folders,
            'currentFolder' => $folder,
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
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:20480'],
            'folder' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\-]*$/'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $folder = (string) ($validated['folder'] ?? '');
        $count = 0;

        foreach ($request->file('files', []) as $file) {
            $this->mediaAdminService->create(
                $file,
                $validated['alt_text'] ?? null,
                $validated['caption'] ?? null,
                $folder,
            );
            $count++;
        }

        return redirect()->route('admin.media.manage', $folder !== '' ? ['folder' => $folder] : [])
            ->with('status', $count . ' fichier(s) uploadé(s).');
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
