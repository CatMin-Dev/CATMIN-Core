<?php

namespace Addons\CatminSlider\Controllers\Admin;

use Addons\CatminSlider\Models\Slider;
use Addons\CatminSlider\Services\SliderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SliderController extends Controller
{
    public function __construct(private readonly SliderService $sliderService)
    {
    }

    public function index(Request $request): View
    {
        $sliders = $this->sliderService->paginate(
            20,
            $request->string('q')->toString() ?: null,
            $request->has('active') ? (bool) $request->input('active') : null,
        );

        return view('catmin-slider::admin.index', compact('sliders'));
    }

    public function create(): View
    {
        return view('catmin-slider::admin.create', [
            'types' => Slider::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:fullwidth,carousel,grid'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'settings_height' => ['nullable', 'string', 'max:32', 'regex:/^\d+(px|vh|em|rem|%)$/'],
            'settings_autoplay' => ['nullable', 'boolean'],
            'settings_interval' => ['nullable', 'integer', 'min:500', 'max:30000'],
            'settings_show_controls' => ['nullable', 'boolean'],
            'settings_show_indicators' => ['nullable', 'boolean'],
            'settings_scroll_speed' => ['nullable', 'integer', 'min:500', 'max:30000'],
            'settings_gap' => ['nullable', 'string', 'max:16', 'regex:/^\d+(px|rem|em)$/'],
            'settings_columns' => ['nullable', 'integer', 'in:4,5,6'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['settings_autoplay'] = $request->boolean('settings_autoplay');
        $data['settings_show_controls'] = $request->boolean('settings_show_controls');
        $data['settings_show_indicators'] = $request->boolean('settings_show_indicators');

        $slider = $this->sliderService->create($data);

        return redirect()
            ->route('admin.slider.edit', $slider->id)
            ->with('status', 'Slider créé avec succès.');
    }

    public function edit(Slider $slider): View
    {
        $slider->load('items');

        return view('catmin-slider::admin.edit', [
            'slider' => $slider,
            'types' => Slider::TYPES,
            'linkTypes' => \Addons\CatminSlider\Models\SliderItem::LINK_TYPES,
        ]);
    }

    public function update(Request $request, Slider $slider): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:fullwidth,carousel,grid'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'settings_height' => ['nullable', 'string', 'max:32', 'regex:/^\d+(px|vh|em|rem|%)$/'],
            'settings_autoplay' => ['nullable', 'boolean'],
            'settings_interval' => ['nullable', 'integer', 'min:500', 'max:30000'],
            'settings_show_controls' => ['nullable', 'boolean'],
            'settings_show_indicators' => ['nullable', 'boolean'],
            'settings_scroll_speed' => ['nullable', 'integer', 'min:500', 'max:30000'],
            'settings_gap' => ['nullable', 'string', 'max:16', 'regex:/^\d+(px|rem|em)$/'],
            'settings_columns' => ['nullable', 'integer', 'in:4,5,6'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['settings_autoplay'] = $request->boolean('settings_autoplay');
        $data['settings_show_controls'] = $request->boolean('settings_show_controls');
        $data['settings_show_indicators'] = $request->boolean('settings_show_indicators');

        $this->sliderService->update($slider, $data);

        return redirect()
            ->route('admin.slider.edit', $slider->id)
            ->with('status', 'Slider mis à jour.');
    }

    public function destroy(Slider $slider): RedirectResponse
    {
        $this->sliderService->delete($slider);

        return redirect()
            ->route('admin.slider.index')
            ->with('status', 'Slider supprimé.');
    }

    public function toggle(Slider $slider): RedirectResponse
    {
        $this->sliderService->toggle($slider);

        return back()->with('status', $slider->is_active ? 'Slider activé.' : 'Slider désactivé.');
    }
}
