<?php

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaAsset;

class MediaAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MediaAsset>
     */
    public function listing()
    {
        return MediaAsset::query()
            ->orderByDesc('id')
            ->get();
    }

    public function create(UploadedFile $file, ?string $altText = null, ?string $caption = null): MediaAsset
    {
        $disk = 'public';
        $directory = 'media';
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
