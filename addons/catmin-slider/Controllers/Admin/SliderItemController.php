<?php

namespace Addons\CatminSlider\Controllers\Admin;

use Addons\CatminSlider\Models\Slider;
use Addons\CatminSlider\Models\SliderItem;
use Addons\CatminSlider\Services\SliderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SliderItemController extends Controller
{
    public function __construct(private readonly SliderService $sliderService)
    {
    }

    public function store(Request $request, Slider $slider): RedirectResponse
    {
        $data = $this->validateItemData($request);

        $this->sliderService->addItem($slider, $data);

        return redirect()
            ->route('admin.slider.edit', $slider->id)
            ->with('status', 'Élément ajouté au slider.');
    }

    public function update(Request $request, Slider $slider, SliderItem $sliderItem): RedirectResponse
    {
        abort_if($sliderItem->slider_id !== $slider->id, 403);

        $data = $this->validateItemData($request);

        $this->sliderService->updateItem($sliderItem, $data);

        return redirect()
            ->route('admin.slider.edit', $slider->id)
            ->with('status', 'Élément mis à jour.');
    }

    public function destroy(Slider $slider, SliderItem $sliderItem): RedirectResponse
    {
        abort_if($sliderItem->slider_id !== $slider->id, 403);

        $this->sliderService->deleteItem($sliderItem);

        return redirect()
            ->route('admin.slider.edit', $slider->id)
            ->with('status', 'Élément supprimé.');
    }

    public function reorder(Request $request, Slider $slider): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer'],
        ]);

        $this->sliderService->reorderItems($slider, (array) $validated['order']);

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function validateItemData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:191'],
            'content' => ['nullable', 'string', 'max:2000'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:600', 'url'],
            'media_id' => ['nullable', 'integer'],
            'media_url' => ['nullable', 'string', 'max:600', 'url'],
            'link_type' => ['nullable', 'in:page,article,event,product,url'],
            'link_id' => ['nullable', 'integer'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
