<?php

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaAsset;

class MediaAdminService
{
    public function listing(?string $folder = null)
    {
        return MediaAsset::query()
            ->when(
                $folder !== null && $folder !== '',
                fn ($q) => $q->where('path', 'like', 'media/' . $folder . '/%'),
                fn ($q) => $q
            )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function folders(): array
    {
        return MediaAsset::query()
            ->get(['path'])
            ->map(function (MediaAsset $m) {
                $rel = str_replace('media/', '', (string) $m->path);
                $parts = explode('/', $rel);

                return count($parts) > 1 ? $parts[0] : '';
            })
            ->filter(fn ($f) => $f !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function create(UploadedFile $file, ?string $altText = null, ?string $caption = null, string $folder = ''): MediaAsset
    {
        $disk = 'public';
        $safeFolder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $folder);
        $directory = $safeFolder !== '' ? 'media/' . $safeFolder : 'media';
        $hashedName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $hashedName, $disk);

        /** @var MediaAsset $asset */
        $asset = MediaAsset::query()->create([
            'disk' => $disk,
            'path' => $path,
            'filename' => $hashedName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size_bytes' => $file->getSize() ?: 0,
            'alt_text' => $altText,
            'caption' => $caption,
            'metadata' => [
                'uploaded_at' => now()->toDateTimeString(),
            ],
            'uploaded_by_id' => Auth::id(),
        ]);

        return $asset;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(MediaAsset $asset, array $payload): MediaAsset
    {
        $asset->fill([
            'alt_text' => $payload['alt_text'] ?? null,
            'caption' => $payload['caption'] ?? null,
        ]);

        $asset->save();

        return $asset;
    }

    public function destroy(MediaAsset $asset): void
    {
        if ($asset->path !== '' && Storage::disk($asset->disk)->exists($asset->path)) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->delete();
    }

    public function previewUrl(MediaAsset $asset): ?string
    {
        if (!str_starts_with((string) $asset->mime_type, 'image/')) {
            return null;
        }

        if ($asset->disk !== 'public') {
            return null;
        }

        return asset('storage/' . ltrim($asset->path, '/'));
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
