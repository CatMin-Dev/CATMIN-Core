<?php

namespace Modules\Media\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Media\Models\MediaAsset;

class MediaAdminService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function listing(array $filters = [], int $perPage = 24, string $scope = 'active'): LengthAwarePaginator
    {
        $query = $this->buildListingQuery($filters, $scope);

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function pickerListing(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->buildListingQuery($filters, 'active');

        return $query
            ->paginate($perPage)
            ->withQueryString();
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

        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $allowed = (array) config('catmin.uploads.allowed_extensions', []);

        if ($extension === '' || !in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('Extension de fichier non autorisee.');
        }

        $hashedName = Str::uuid()->toString() . '.' . $extension;
        $path = $file->storeAs($directory, $hashedName, $disk);
        $detectedMime = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');

        /** @var MediaAsset $asset */
        $asset = MediaAsset::query()->create([
            'disk' => $disk,
            'path' => $path,
            'filename' => $hashedName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $detectedMime,
            'extension' => $extension,
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
     * @return array<string, mixed>
     */
    public function toPickerItem(MediaAsset $asset): array
    {
        return [
            'id' => (int) $asset->id,
            'original_name' => (string) $asset->original_name,
            'filename' => (string) $asset->filename,
            'mime_type' => (string) ($asset->mime_type ?? ''),
            'extension' => (string) ($asset->extension ?? ''),
            'size_bytes' => (int) $asset->size_bytes,
            'size_human' => $this->humanSize((int) $asset->size_bytes),
            'file_url' => $this->fileUrl($asset),
            'folder' => $this->folderFromPath((string) $asset->path),
            'kind' => $this->assetKind($asset),
            'preview_url' => $this->previewUrl($asset),
            'created_at' => optional($asset->created_at)?->toIso8601String(),
            'caption' => (string) ($asset->caption ?? ''),
            'alt_text' => (string) ($asset->alt_text ?? ''),
        ];
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
        $asset->delete();
    }

    public function restore(MediaAsset $asset): void
    {
        $asset->restore();
    }

    public function forceDelete(MediaAsset $asset): void
    {
        if ($asset->path !== '' && Storage::disk($asset->disk)->exists($asset->path)) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->forceDelete();
    }

    public function emptyTrash(): int
    {
        $trashed = MediaAsset::onlyTrashed()->get();
        $count = 0;

        foreach ($trashed as $asset) {
            $this->forceDelete($asset);
            $count++;
        }

        return $count;
    }

    public function purgeTrashOlderThan(int $days): int
    {
        $safeDays = max(1, $days);
        $threshold = now()->subDays($safeDays);

        $trashed = MediaAsset::onlyTrashed()
            ->where(function (Builder $query) use ($threshold): void {
                $query->whereNotNull('deleted_at')
                    ->where('deleted_at', '<=', $threshold);
            })
            ->get();

        $count = 0;
        foreach ($trashed as $asset) {
            $this->forceDelete($asset);
            $count++;
        }

        return $count;
    }

    public function previewUrl(MediaAsset $asset): ?string
    {
        if (!str_starts_with((string) $asset->mime_type, 'image/')) {
            return null;
        }

        return $this->fileUrl($asset);
    }

    public function fileUrl(MediaAsset $asset): ?string
    {
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

    public function folderFromPath(string $path): string
    {
        if (!str_starts_with($path, 'media/')) {
            return '';
        }

        $rel = substr($path, strlen('media/'));
        $parts = explode('/', $rel);

        return count($parts) > 1 ? (string) $parts[0] : '';
    }

    public function assetKind(MediaAsset $asset): string
    {
        $mimeType = (string) ($asset->mime_type ?? '');

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (
            str_starts_with($mimeType, 'text/')
            || str_contains($mimeType, 'pdf')
            || str_contains($mimeType, 'officedocument')
            || str_contains($mimeType, 'msword')
            || str_contains($mimeType, 'spreadsheet')
            || str_contains($mimeType, 'presentation')
        ) {
            return 'document';
        }

        return 'other';
    }

    public function previewMode(MediaAsset $asset): string
    {
        if ($this->previewUrl($asset) !== null) {
            return 'image';
        }

        $kind = $this->assetKind($asset);
        if ($kind === 'document' && $this->fileUrl($asset) !== null) {
            return 'document';
        }

        return 'none';
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildListingQuery(array $filters, string $scope = 'active')
    {
        $folder = trim((string) ($filters['folder'] ?? ''));
        $search = trim((string) ($filters['q'] ?? ''));
        $kind = trim((string) ($filters['kind'] ?? ''));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        $sort = trim((string) ($filters['sort'] ?? 'newest'));

        $query = MediaAsset::query()
            ->select([
                'id',
                'disk',
                'path',
                'filename',
                'original_name',
                'mime_type',
                'extension',
                'size_bytes',
                'alt_text',
                'caption',
                'metadata',
                'uploaded_by_id',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->when(
                $folder !== '',
                fn ($builder) => $builder->where('path', 'like', 'media/' . $folder . '/%')
            )
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($q) use ($search): void {
                    $q->where('original_name', 'like', '%' . $search . '%')
                        ->orWhere('filename', 'like', '%' . $search . '%')
                        ->orWhere('alt_text', 'like', '%' . $search . '%')
                        ->orWhere('caption', 'like', '%' . $search . '%')
                        ->orWhere('path', 'like', '%' . $search . '%')
                        ->orWhere('mime_type', 'like', '%' . $search . '%')
                        ->orWhere('extension', 'like', '%' . $search . '%');
                });
            })
            ->when($kind === 'image', fn ($builder) => $builder->where('mime_type', 'like', 'image/%'))
            ->when($kind === 'video', fn ($builder) => $builder->where('mime_type', 'like', 'video/%'))
            ->when($kind === 'audio', fn ($builder) => $builder->where('mime_type', 'like', 'audio/%'))
            ->when($kind === 'document', function ($builder): void {
                $builder->where(function ($q): void {
                    $q->where('mime_type', 'like', 'text/%')
                        ->orWhere('mime_type', 'like', '%pdf%')
                        ->orWhere('mime_type', 'like', '%officedocument%')
                        ->orWhere('mime_type', 'like', '%msword%')
                        ->orWhere('mime_type', 'like', '%spreadsheet%')
                        ->orWhere('mime_type', 'like', '%presentation%');
                });
            })
            ->when($kind === 'other', function ($builder): void {
                $builder->where(function ($q): void {
                    $q->where(function ($inner): void {
                        $inner->where('mime_type', 'not like', 'image/%')
                            ->where('mime_type', 'not like', 'video/%')
                            ->where('mime_type', 'not like', 'audio/%')
                            ->where('mime_type', 'not like', 'text/%')
                            ->where('mime_type', 'not like', '%pdf%')
                            ->where('mime_type', 'not like', '%officedocument%')
                            ->where('mime_type', 'not like', '%msword%')
                            ->where('mime_type', 'not like', '%spreadsheet%')
                            ->where('mime_type', 'not like', '%presentation%');
                    })->orWhereNull('mime_type');
                });
            })
            ->when($from !== '', fn ($builder) => $builder->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($builder) => $builder->whereDate('created_at', '<=', $to));

        if ($scope === 'trash') {
            $query->onlyTrashed();
        } elseif ($scope === 'all') {
            $query->withTrashed();
        }

        return match ($sort) {
            'oldest' => $query->orderBy('id'),
            'name' => $query->orderBy('original_name')->orderByDesc('id'),
            'type' => $query->orderBy('mime_type')->orderBy('extension')->orderByDesc('id'),
            'size_asc' => $query->orderBy('size_bytes')->orderByDesc('id'),
            'size_desc' => $query->orderByDesc('size_bytes')->orderByDesc('id'),
            'updated' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }

    public function bulkTrash(array $ids): int
    {
        return MediaAsset::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }
}
