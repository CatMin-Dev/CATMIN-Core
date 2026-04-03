<?php

namespace Addons\CatminSlider\Services;

use Addons\CatminSlider\Models\Slider;
use Addons\CatminSlider\Models\SliderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SliderRenderService
{
    public function tableExists(): bool
    {
        return Schema::hasTable('sliders') && Schema::hasTable('slider_items');
    }

    /**
     * Resolve a slider by slug and return its structured render data.
     * Returns null if the slider is inactive or not found.
     *
     * @return array<string, mixed>|null
     */
    public function forSlug(string $slug): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $slider = Slider::query()->where('slug', $slug)->first();

        if ($slider === null || !$slider->isCurrentlyActive()) {
            return null;
        }

        return $this->render($slider);
    }

    /**
     * Build full render payload for a slider.
     * @return array<string, mixed>
     */
    public function render(Slider $slider): array
    {
        $settings = $slider->mergedSettings();
        $items = $slider->activeItems()->get();

        return match ($slider->type) {
            Slider::TYPE_FULLWIDTH => $this->renderFullwidth($slider, $settings, $items),
            Slider::TYPE_CAROUSEL => $this->renderCarousel($slider, $settings, $items),
            Slider::TYPE_GRID => $this->renderGrid($slider, $settings, $items),
            default => $this->renderFullwidth($slider, $settings, $items),
        };
    }

    /**
     * @param Collection<int, SliderItem> $items
     * @return array<string, mixed>
     */
    private function renderFullwidth(Slider $slider, array $settings, Collection $items): array
    {
        return [
            'type' => Slider::TYPE_FULLWIDTH,
            'slider_id' => $slider->id,
            'slug' => $slider->slug,
            'name' => $slider->name,
            'height' => $settings['height'] ?? '500px',
            'autoplay' => (bool) ($settings['autoplay'] ?? true),
            'interval' => (int) ($settings['interval'] ?? 5000),
            'show_controls' => (bool) ($settings['show_controls'] ?? true),
            'show_indicators' => (bool) ($settings['show_indicators'] ?? true),
            'items' => $this->mapItems($items),
            'view' => 'catmin-slider::render.fullwidth',
        ];
    }

    /**
     * @param Collection<int, SliderItem> $items
     * @return array<string, mixed>
     */
    private function renderCarousel(Slider $slider, array $settings, Collection $items): array
    {
        return [
            'type' => Slider::TYPE_CAROUSEL,
            'slider_id' => $slider->id,
            'slug' => $slider->slug,
            'name' => $slider->name,
            'height' => $settings['height'] ?? '120px',
            'scroll_speed' => (int) ($settings['scroll_speed'] ?? 3000),
            'gap' => $settings['gap'] ?? '24px',
            'items' => $this->mapItems($items),
            'view' => 'catmin-slider::render.carousel',
        ];
    }

    /**
     * @param Collection<int, SliderItem> $items
     * @return array<string, mixed>
     */
    private function renderGrid(Slider $slider, array $settings, Collection $items): array
    {
        $columns = max(1, min(6, (int) ($settings['columns'] ?? 4)));

        return [
            'type' => Slider::TYPE_GRID,
            'slider_id' => $slider->id,
            'slug' => $slider->slug,
            'name' => $slider->name,
            'height' => $settings['height'] ?? '300px',
            'columns' => $columns,
            'items' => $this->mapItems($items),
            'view' => 'catmin-slider::render.grid',
        ];
    }

    /**
     * @param Collection<int, SliderItem> $items
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(Collection $items): array
    {
        return $items->map(fn (SliderItem $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'subtitle' => $item->subtitle,
            'content' => $item->content,
            'cta_label' => $item->cta_label,
            'cta_url' => $item->cta_url,
            'image_url' => $item->resolvedImageUrl(),
            'link_type' => $item->link_type,
            'link_id' => $item->link_id,
        ])->values()->all();
    }
}
