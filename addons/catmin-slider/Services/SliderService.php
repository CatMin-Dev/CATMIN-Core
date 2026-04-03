<?php

namespace Addons\CatminSlider\Services;

use Addons\CatminSlider\Models\Slider;
use Addons\CatminSlider\Models\SliderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SliderService
{
    public function tableExists(): bool
    {
        return Schema::hasTable('sliders');
    }

    /** @return LengthAwarePaginator<Slider> */
    public function paginate(int $perPage = 20, ?string $search = null, ?bool $active = null): LengthAwarePaginator
    {
        $query = Slider::query()->orderBy('name');

        if ($search !== null && $search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('slug', 'like', '%' . $search . '%'));
        }

        if ($active !== null) {
            $query->where('is_active', $active);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Slider
    {
        $settings = $this->extractSettings($data);

        return Slider::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug((string) ($data['slug'] ?? $data['name'])),
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? Slider::TYPE_FULLWIDTH,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'settings' => $settings ?: null,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Slider $slider, array $data): Slider
    {
        $settings = $this->extractSettings($data);

        $slider->update([
            'name' => $data['name'] ?? $slider->name,
            'slug' => $this->uniqueSlug((string) ($data['slug'] ?? $slider->slug), $slider->id),
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? $slider->type,
            'is_active' => (bool) ($data['is_active'] ?? $slider->is_active),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'settings' => $settings ?: null,
        ]);

        return $slider->refresh();
    }

    public function delete(Slider $slider): bool
    {
        return (bool) $slider->delete();
    }

    public function toggle(Slider $slider): Slider
    {
        $slider->update(['is_active' => !$slider->is_active]);
        return $slider->refresh();
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     */
    public function addItem(Slider $slider, array $data): SliderItem
    {
        $maxPosition = SliderItem::query()
            ->where('slider_id', $slider->id)
            ->max('position') ?? -1;

        return SliderItem::query()->create([
            'slider_id' => $slider->id,
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'content' => $data['content'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'media_id' => isset($data['media_id']) ? (int) $data['media_id'] : null,
            'media_url' => $data['media_url'] ?? null,
            'link_type' => $data['link_type'] ?? null,
            'link_id' => isset($data['link_id']) ? (int) $data['link_id'] : null,
            'position' => (int) ($data['position'] ?? ($maxPosition + 1)),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateItem(SliderItem $item, array $data): SliderItem
    {
        $item->update([
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'content' => $data['content'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'media_id' => isset($data['media_id']) ? (int) $data['media_id'] : null,
            'media_url' => $data['media_url'] ?? null,
            'link_type' => $data['link_type'] ?? null,
            'link_id' => isset($data['link_id']) ? (int) $data['link_id'] : null,
            'position' => isset($data['position']) ? (int) $data['position'] : $item->position,
            'is_active' => (bool) ($data['is_active'] ?? $item->is_active),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return $item->refresh();
    }

    public function deleteItem(SliderItem $item): bool
    {
        return (bool) $item->delete();
    }

    /**
     * Reorder items by array of IDs.
     * @param array<int, int> $orderedIds
     */
    public function reorderItems(Slider $slider, array $orderedIds): void
    {
        foreach ($orderedIds as $position => $itemId) {
            SliderItem::query()
                ->where('id', $itemId)
                ->where('slider_id', $slider->id)
                ->update(['position' => $position]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function uniqueSlug(string $value, ?int $excludeId = null): string
    {
        $slug = Str::slug($value);
        $original = $slug;
        $counter = 1;

        $query = Slider::query()->where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $original . '-' . $counter++;
            $query = Slider::query()->where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Extract typed settings from raw form data.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractSettings(array $data): array
    {
        $settings = [];

        // Fullwidth settings
        if (isset($data['settings_height']) && $data['settings_height'] !== '') {
            $settings['height'] = (string) $data['settings_height'];
        }
        if (isset($data['settings_autoplay'])) {
            $settings['autoplay'] = (bool) $data['settings_autoplay'];
        }
        if (isset($data['settings_interval']) && $data['settings_interval'] !== '') {
            $settings['interval'] = (int) $data['settings_interval'];
        }
        if (isset($data['settings_show_controls'])) {
            $settings['show_controls'] = (bool) $data['settings_show_controls'];
        }
        if (isset($data['settings_show_indicators'])) {
            $settings['show_indicators'] = (bool) $data['settings_show_indicators'];
        }

        // Carousel settings
        if (isset($data['settings_scroll_speed']) && $data['settings_scroll_speed'] !== '') {
            $settings['scroll_speed'] = (int) $data['settings_scroll_speed'];
        }
        if (isset($data['settings_gap']) && $data['settings_gap'] !== '') {
            $settings['gap'] = (string) $data['settings_gap'];
        }

        // Grid settings
        if (isset($data['settings_columns']) && $data['settings_columns'] !== '') {
            $settings['columns'] = (int) $data['settings_columns'];
        }

        return $settings;
    }
}
