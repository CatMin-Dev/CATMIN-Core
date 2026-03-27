<?php

namespace Modules\Blocks\Services;

use Illuminate\Support\Str;
use Modules\Blocks\Models\Block;

class BlockAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Block>
     */
    public function listing()
    {
        return Block::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Block
    {
        $slug = $this->uniqueSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''));

        /** @var Block $block */
        $block = Block::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'active'),
        ]);

        return $block;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Block $block, array $payload): Block
    {
        $slug = $this->uniqueSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $block->id);

        $block->fill([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'active'),
        ]);

        $block->save();

        return $block;
    }

    public function toggleStatus(Block $block): Block
    {
        $block->status = $block->status === 'active' ? 'inactive' : 'active';
        $block->save();

        return $block;
    }

    public function findActiveBySlug(string $slug): ?Block
    {
        return Block::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    private function uniqueSlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'block';

        $slug = $baseSlug;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Block::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
