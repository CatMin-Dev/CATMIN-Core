{{--
    Render: infinite continuous carousel (pure CSS animation — no JS required)
    Variables: $data (array from SliderRenderService::render())
    Usage: @include('catmin-slider::render.carousel', ['data' => $data])
--}}
@php
    $carouselId   = 'cs-car-' . ($data['slider_id'] ?? uniqid());
    $height       = $data['height'] ?? '120px';
    $scrollSpeed  = (int) ($data['scroll_speed'] ?? 3000);
    $gap          = $data['gap'] ?? '24px';
    $items        = $data['items'] ?? [];
    // Duplicate items for seamless loop
    $loopItems    = array_merge($items, $items);
    $animDuration = (count($items) * $scrollSpeed / 1000);  // seconds
@endphp

{{-- Scoped styles --}}
<style>
.catmin-carousel-{{ $carouselId }} {
    overflow: hidden;
    width: 100%;
    position: relative;
}
.catmin-carousel-{{ $carouselId }}::before,
.catmin-carousel-{{ $carouselId }}::after {
    content: '';
    position: absolute;
    top: 0;
    width: 80px;
    height: 100%;
    z-index: 2;
    pointer-events: none;
}
.catmin-carousel-{{ $carouselId }}::before {
    left: 0;
    background: linear-gradient(to right, var(--bs-body-bg, #fff) 0%, transparent 100%);
}
.catmin-carousel-{{ $carouselId }}::after {
    right: 0;
    background: linear-gradient(to left, var(--bs-body-bg, #fff) 0%, transparent 100%);
}
.catmin-carousel-{{ $carouselId }} .catmin-carousel-track {
    display: flex;
    gap: {{ $gap }};
    animation: catmin-scroll-{{ $carouselId }} {{ $animDuration }}s linear infinite;
    width: max-content;
    will-change: transform;
}
.catmin-carousel-{{ $carouselId }}:hover .catmin-carousel-track {
    animation-play-state: paused;
}
.catmin-carousel-{{ $carouselId }} .catmin-carousel-item {
    flex-shrink: 0;
    height: {{ $height }};
    display: flex;
    align-items: center;
    justify-content: center;
}
.catmin-carousel-{{ $carouselId }} .catmin-carousel-item img {
    height: 100%;
    width: auto;
    object-fit: contain;
    display: block;
}
@keyframes catmin-scroll-{{ $carouselId }} {
    0%   { transform: translateX(0); }
    100% { transform: translateX(calc(-50% - {{ $gap }} / 2)); }
}
</style>

<div class="catmin-carousel-{{ $carouselId }}">
    <div class="catmin-carousel-track" aria-hidden="true">
        @foreach($loopItems as $item)
            <div class="catmin-carousel-item">
                @if(!empty($item['image_url']))
                    <img src="{{ $item['image_url'] }}"
                         alt="{{ $item['title'] ?? '' }}"
                         title="{{ $item['title'] ?? '' }}"
                         loading="lazy">
                @elseif(!empty($item['title']))
                    <span class="text-muted fw-semibold px-4">{{ $item['title'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
