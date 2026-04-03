<?php

namespace Tests\Unit\Slider;

use Addons\CatminSlider\Models\Slider;
use Addons\CatminSlider\Models\SliderItem;
use Addons\CatminSlider\Services\SliderRenderService;
use Addons\CatminSlider\Services\SliderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SliderServiceTest extends TestCase
{
    protected SliderService $sliderService;
    protected SliderRenderService $renderService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        if (!Schema::hasTable('sliders')) {
            Schema::create('sliders', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->enum('type', ['fullwidth', 'carousel', 'grid'])->default('fullwidth');
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('slider_items')) {
            Schema::create('slider_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('slider_id');
                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();
                $table->text('content')->nullable();
                $table->string('cta_label')->nullable();
                $table->string('cta_url')->nullable();
                $table->unsignedBigInteger('media_id')->nullable();
                $table->string('media_url')->nullable();
                $table->enum('link_type', ['page', 'article', 'event', 'product', 'url'])->nullable();
                $table->unsignedBigInteger('link_id')->nullable();
                $table->unsignedSmallInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        $this->sliderService = new SliderService();
        $this->renderService = new SliderRenderService();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Slider CRUD
    // ──────────────────────────────────────────────────────────────────────────

    public function test_create_fullwidth_slider(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Hero Banner',
            'type' => 'fullwidth',
            'is_active' => true,
            'settings_height' => '600px',
            'settings_autoplay' => true,
            'settings_interval' => 4000,
            'settings_show_controls' => true,
            'settings_show_indicators' => true,
        ]);

        $this->assertInstanceOf(Slider::class, $slider);
        $this->assertEquals('Hero Banner', $slider->name);
        $this->assertEquals('hero-banner', $slider->slug);
        $this->assertEquals(Slider::TYPE_FULLWIDTH, $slider->type);
        $this->assertTrue($slider->is_active);

        $settings = $slider->settings;
        $this->assertEquals('600px', $settings['height']);
        $this->assertEquals(4000, $settings['interval']);
        $this->assertTrue($settings['autoplay']);
    }

    public function test_create_carousel_slider(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Logo Carousel',
            'type' => 'carousel',
            'is_active' => true,
            'settings_height' => '100px',
            'settings_scroll_speed' => 2000,
            'settings_gap' => '32px',
        ]);

        $this->assertEquals(Slider::TYPE_CAROUSEL, $slider->type);
        $settings = $slider->settings;
        $this->assertEquals('100px', $settings['height']);
        $this->assertEquals(2000, $settings['scroll_speed']);
        $this->assertEquals('32px', $settings['gap']);
    }

    public function test_create_grid_slider(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Photo Grid',
            'type' => 'grid',
            'is_active' => true,
            'settings_columns' => 5,
            'settings_height' => '250px',
        ]);

        $this->assertEquals(Slider::TYPE_GRID, $slider->type);
        $settings = $slider->settings;
        $this->assertEquals(5, $settings['columns']);
        $this->assertEquals('250px', $settings['height']);
    }

    public function test_update_slider(): void
    {
        $slider = $this->sliderService->create(['name' => 'Initial', 'type' => 'fullwidth']);

        $updated = $this->sliderService->update($slider, [
            'name' => 'Updated Name',
            'type' => 'grid',
            'is_active' => false,
            'settings_columns' => 6,
            'settings_height' => '400px',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals(Slider::TYPE_GRID, $updated->type);
        $this->assertFalse($updated->is_active);
    }

    public function test_delete_slider(): void
    {
        $slider = $this->sliderService->create(['name' => 'To Delete', 'type' => 'fullwidth']);
        $id = $slider->id;

        $this->sliderService->delete($slider);

        $this->assertNull(Slider::query()->find($id));
    }

    public function test_toggle_slider(): void
    {
        $slider = $this->sliderService->create(['name' => 'Toggle Test', 'type' => 'fullwidth', 'is_active' => true]);

        $toggled = $this->sliderService->toggle($slider);
        $this->assertFalse($toggled->is_active);

        $toggled2 = $this->sliderService->toggle($toggled);
        $this->assertTrue($toggled2->is_active);
    }

    public function test_slug_is_unique_on_duplicate(): void
    {
        $s1 = $this->sliderService->create(['name' => 'My Slider', 'type' => 'fullwidth']);
        $s2 = $this->sliderService->create(['name' => 'My Slider', 'type' => 'carousel']);

        $this->assertEquals('my-slider', $s1->slug);
        $this->assertEquals('my-slider-1', $s2->slug);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Items CRUD + ordering
    // ──────────────────────────────────────────────────────────────────────────

    public function test_add_items_to_slider(): void
    {
        $slider = $this->sliderService->create(['name' => 'Slider With Items', 'type' => 'fullwidth']);

        $item1 = $this->sliderService->addItem($slider, [
            'title' => 'Slide One',
            'media_url' => 'https://example.com/img1.jpg',
            'cta_label' => 'Voir plus',
            'cta_url' => 'https://example.com',
            'is_active' => true,
        ]);

        $item2 = $this->sliderService->addItem($slider, [
            'title' => 'Slide Two',
            'media_url' => 'https://example.com/img2.jpg',
        ]);

        $this->assertEquals(0, $item1->position);
        $this->assertEquals(1, $item2->position);
        $this->assertEquals('Slide One', $item1->title);
        $this->assertEquals('https://example.com/img1.jpg', $item1->media_url);
    }

    public function test_update_item(): void
    {
        $slider = $this->sliderService->create(['name' => 'S', 'type' => 'carousel']);
        $item = $this->sliderService->addItem($slider, ['title' => 'Original']);

        $updated = $this->sliderService->updateItem($item, [
            'title' => 'Updated',
            'subtitle' => 'New subtitle',
            'is_active' => false,
        ]);

        $this->assertEquals('Updated', $updated->title);
        $this->assertEquals('New subtitle', $updated->subtitle);
        $this->assertFalse($updated->is_active);
    }

    public function test_delete_item(): void
    {
        $slider = $this->sliderService->create(['name' => 'S2', 'type' => 'fullwidth']);
        $item = $this->sliderService->addItem($slider, ['title' => 'Item to delete']);
        $itemId = $item->id;

        $this->sliderService->deleteItem($item);

        $this->assertNull(SliderItem::query()->find($itemId));
    }

    public function test_reorder_items(): void
    {
        $slider = $this->sliderService->create(['name' => 'Reorder Test', 'type' => 'grid']);
        $item1 = $this->sliderService->addItem($slider, ['title' => 'A']);
        $item2 = $this->sliderService->addItem($slider, ['title' => 'B']);
        $item3 = $this->sliderService->addItem($slider, ['title' => 'C']);

        // Reverse order
        $this->sliderService->reorderItems($slider, [$item3->id, $item2->id, $item1->id]);

        $this->assertEquals(0, SliderItem::find($item3->id)->position);
        $this->assertEquals(1, SliderItem::find($item2->id)->position);
        $this->assertEquals(2, SliderItem::find($item1->id)->position);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Period activation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_slider_with_future_start_is_not_active(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Future Slider',
            'type' => 'fullwidth',
            'is_active' => true,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);

        $this->assertFalse($slider->isCurrentlyActive());
    }

    public function test_slider_with_past_end_is_not_active(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Expired Slider',
            'type' => 'fullwidth',
            'is_active' => true,
            'ends_at' => now()->subDay()->toDateTimeString(),
        ]);

        $this->assertFalse($slider->isCurrentlyActive());
    }

    public function test_slider_within_period_is_active(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Active Period Slider',
            'type' => 'fullwidth',
            'is_active' => true,
            'starts_at' => now()->subDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
        ]);

        $this->assertTrue($slider->isCurrentlyActive());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Render service
    // ──────────────────────────────────────────────────────────────────────────

    public function test_render_fullwidth_returns_correct_structure(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Render Test FW',
            'type' => 'fullwidth',
            'is_active' => true,
            'settings_height' => '450px',
            'settings_autoplay' => true,
        ]);
        $this->sliderService->addItem($slider, ['title' => 'Slide 1', 'media_url' => 'https://img.example.com/1.jpg']);
        $this->sliderService->addItem($slider, ['title' => 'Slide 2', 'media_url' => 'https://img.example.com/2.jpg']);

        $data = $this->renderService->render($slider);

        $this->assertEquals('fullwidth', $data['type']);
        $this->assertEquals('450px', $data['height']);
        $this->assertTrue($data['autoplay']);
        $this->assertCount(2, $data['items']);
        $this->assertEquals('Slide 1', $data['items'][0]['title']);
    }

    public function test_render_carousel_returns_correct_structure(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Render Carousel',
            'type' => 'carousel',
            'is_active' => true,
            'settings_height' => '90px',
            'settings_scroll_speed' => 2500,
        ]);
        $this->sliderService->addItem($slider, ['title' => 'Brand A', 'media_url' => 'https://img.example.com/a.png']);

        $data = $this->renderService->render($slider);

        $this->assertEquals('carousel', $data['type']);
        $this->assertEquals('90px', $data['height']);
        $this->assertEquals(2500, $data['scroll_speed']);
        $this->assertCount(1, $data['items']);
    }

    public function test_render_grid_returns_correct_structure(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Render Grid',
            'type' => 'grid',
            'is_active' => true,
            'settings_columns' => 6,
            'settings_height' => '200px',
        ]);
        for ($i = 1; $i <= 6; $i++) {
            $this->sliderService->addItem($slider, ['title' => "Cell $i"]);
        }

        $data = $this->renderService->render($slider);

        $this->assertEquals('grid', $data['type']);
        $this->assertEquals(6, $data['columns']);
        $this->assertEquals('200px', $data['height']);
        $this->assertCount(6, $data['items']);
    }

    public function test_render_grid_columns_clamped_to_max_6(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Clamped Grid',
            'type' => 'grid',
            'is_active' => true,
            'settings_columns' => 99,
            'settings_height' => '300px',
        ]);

        $data = $this->renderService->render($slider);
        $this->assertLessThanOrEqual(6, $data['columns']);
    }

    public function test_for_slug_returns_null_for_inactive_slider(): void
    {
        $slider = $this->sliderService->create([
            'name' => 'Inactive Slug Test',
            'type' => 'fullwidth',
            'is_active' => false,
        ]);

        $result = $this->renderService->forSlug($slider->slug);
        $this->assertNull($result);
    }

    public function test_item_link_type_is_stored(): void
    {
        $slider = $this->sliderService->create(['name' => 'Link Test', 'type' => 'fullwidth']);
        $item = $this->sliderService->addItem($slider, [
            'title' => 'Event Slide',
            'link_type' => 'event',
            'link_id' => 42,
        ]);

        $this->assertEquals('event', $item->link_type);
        $this->assertEquals(42, $item->link_id);
    }

    public function test_inactive_items_excluded_from_active_items(): void
    {
        $slider = $this->sliderService->create(['name' => 'Active Items Test', 'type' => 'carousel', 'is_active' => true]);
        $this->sliderService->addItem($slider, ['title' => 'Active', 'is_active' => true]);
        $this->sliderService->addItem($slider, ['title' => 'Inactive', 'is_active' => false]);

        $this->assertCount(1, $slider->activeItems()->get());
    }

    public function test_merged_settings_fallback_to_defaults(): void
    {
        $slider = $this->sliderService->create(['name' => 'No Settings', 'type' => 'fullwidth']);

        $merged = $slider->mergedSettings();

        $this->assertArrayHasKey('height', $merged);
        $this->assertEquals('500px', $merged['height']);
        $this->assertTrue($merged['autoplay']);
    }
}
