<?php

namespace Modules\Media\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Services\MediaAdminService;

class MediaController extends Controller
{
    public function __construct(private readonly MediaAdminService $mediaAdminService)
    {
    }

    public function index(Request $request): View
    {
        $folder = trim((string) $request->query('folder', ''));
        $kind = trim((string) $request->query('kind', ''));
        $query = trim((string) $request->query('q', ''));
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $sort = trim((string) $request->query('sort', 'newest'));
        $perPageInput = trim((string) $request->query('per_page', '24'));
        $perPage = in_array($perPageInput, ['12', '24', '48', '96'], true)
            ? (int) $perPageInput
            : 24;

        $assets = $this->mediaAdminService->listing([
            'folder' => $folder,
            'kind' => $kind,
            'q' => $query,
            'from' => $from,
            'to' => $to,
            'sort' => $sort,
        ], $perPage);

        $folders = $this->mediaAdminService->folders();

        return view()->file(base_path('modules/Media/Views/index.blade.php'), [
            'currentPage' => 'content-media',
            'assets' => $assets,
            'mediaService' => $this->mediaAdminService,
            'folders' => $folders,
            'currentFolder' => $folder,
            'search' => $query,
            'selectedKind' => $kind,
            'selectedFrom' => $from,
            'selectedTo' => $to,
            'selectedSort' => $sort,
            'selectedPerPage' => (string) $perPage,
        ]);
    }

    public function picker(Request $request): JsonResponse
    {
        $folder = trim((string) $request->query('folder', ''));
        $kind = trim((string) $request->query('kind', ''));
        $query = trim((string) $request->query('q', ''));
        $perPageInput = trim((string) $request->query('per_page', '12'));
        $perPage = in_array($perPageInput, ['12', '24', '48'], true)
            ? (int) $perPageInput
            : 12;

        $assets = $this->mediaAdminService->pickerListing([
            'folder' => $folder,
            'kind' => $kind,
            'q' => $query,
            'sort' => 'newest',
        ], $perPage);

        $items = array_map(
            fn (MediaAsset $asset): array => $this->mediaAdminService->toPickerItem($asset),
            $assets->items()
        );

        return response()->json([
            'data' => array_values($items),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
            'links' => [
                'next' => $assets->nextPageUrl(),
                'prev' => $assets->previousPageUrl(),
            ],
        ]);
    }

    public function pickerItem(MediaAsset $asset): JsonResponse
    {
        return response()->json([
            'data' => $this->mediaAdminService->toPickerItem($asset),
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
        $allowedExtensions = (array) config('catmin.uploads.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'txt', 'csv', 'json',
            'mp4', 'webm', 'mp3',
            'zip',
        ]);
        $maxFileKb = (int) config('catmin.uploads.max_file_kb', 20480);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:' . $maxFileKb, 'mimes:' . implode(',', $allowedExtensions)],
            'folder' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\-]*$/'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $folder = (string) ($validated['folder'] ?? '');
        $count = 0;

        foreach ($request->file('files', []) as $file) {
            try {
                $this->mediaAdminService->create(
                    $file,
                    $validated['alt_text'] ?? null,
                    $validated['caption'] ?? null,
                    $folder,
                );
                $count++;
            } catch (InvalidArgumentException $e) {
                return back()
                    ->withInput()
                    ->withErrors(['files' => $e->getMessage()]);
            }
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
