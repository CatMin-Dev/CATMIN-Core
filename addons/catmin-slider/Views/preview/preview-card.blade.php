{{--
    Admin preview card — compact version embedded in the edit view.
    Shows the actual render at reduced scale inside the admin.
--}}
@php
    use Addons\CatminSlider\Services\SliderRenderService;
    $renderData = app(SliderRenderService::class)->render($slider);
@endphp

<div style="transform-origin:top left;overflow:hidden;">
    @if($slider->type === 'fullwidth')
        @include('catmin-slider::render.fullwidth', ['data' => $renderData])
    @elseif($slider->type === 'carousel')
        @include('catmin-slider::render.carousel', ['data' => $renderData])
    @elseif($slider->type === 'grid')
        @include('catmin-slider::render.grid', ['data' => $renderData])
    @endif
</div>
