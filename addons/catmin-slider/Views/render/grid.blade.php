{{--
    Render: multi-column fullwidth grid (4, 5 or 6 columns)
    Variables: $data (array from SliderRenderService::render())
    Usage: @include('catmin-slider::render.grid', ['data' => $data])
--}}
@php
    $gridId  = 'cs-grid-' . ($data['slider_id'] ?? uniqid());
    $height  = $data['height'] ?? '300px';
    $columns = max(1, min(6, (int) ($data['columns'] ?? 4)));
    $items   = $data['items'] ?? [];
@endphp

<style>
.catmin-grid-{{ $gridId }} {
    display: grid;
    grid-template-columns: repeat({{ $columns }}, 1fr);
    width: 100%;
}
.catmin-grid-{{ $gridId }} .catmin-grid-cell {
    position: relative;
    height: {{ $height }};
    overflow: hidden;
    background: #1a1a2e;
}
.catmin-grid-{{ $gridId }} .catmin-grid-cell img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}
.catmin-grid-{{ $gridId }} .catmin-grid-cell:hover img {
    transform: scale(1.05);
}
.catmin-grid-{{ $gridId }} .catmin-grid-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1rem;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.catmin-grid-{{ $gridId }} .catmin-grid-cell:hover .catmin-grid-overlay {
    opacity: 1;
}
</style>

<div class="catmin-grid-{{ $gridId }}">
    @foreach($items as $item)
        <div class="catmin-grid-cell">
            @if(!empty($item['image_url']))
                <img src="{{ $item['image_url'] }}"
                     alt="{{ $item['title'] ?? '' }}"
                     loading="lazy">
            @endif

            @if(!empty($item['title']) || !empty($item['cta_label']))
                <div class="catmin-grid-overlay text-white">
                    @if(!empty($item['title']))
                        <div class="fw-semibold">{{ $item['title'] }}</div>
                    @endif
                    @if(!empty($item['subtitle']))
                        <div class="small opacity-75">{{ $item['subtitle'] }}</div>
                    @endif
                    @if(!empty($item['cta_label']) && !empty($item['cta_url']))
                        <a href="{{ $item['cta_url'] }}" class="btn btn-sm btn-light mt-1">{{ $item['cta_label'] }}</a>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
