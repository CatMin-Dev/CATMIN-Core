<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Str;
use Modules\Shop\Models\Product;

class ShopAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function listing()
    {
        return Product::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Product
    {
        $slug = $this->uniqueSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''));

        /** @var Product $product */
        $product = Product::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'price' => (float) $payload['price'],
            'description' => (string) ($payload['description'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'inactive'),
        ]);

        return $product;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Product $product, array $payload): Product
    {
        $slug = $this->uniqueSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $product->id);

        $product->fill([
            'name' => (string) $payload['name'],
            'slug' => $slug,
            'price' => (float) $payload['price'],
            'description' => (string) ($payload['description'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'inactive'),
        ]);

        $product->save();

        return $product;
    }

    public function toggleStatus(Product $product): Product
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return $product;
    }

    private function uniqueSlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'product';

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
        return Product::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
