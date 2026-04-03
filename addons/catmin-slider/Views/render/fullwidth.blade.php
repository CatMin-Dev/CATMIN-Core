{{--
    Render: fullwidth slider (Bootstrap carousel)
    Variables: $data (array from SliderRenderService::render())
    Usage: @include('catmin-slider::render.fullwidth', ['data' => $data])
--}}
@php
    $sliderId = 'cs-fw-' . ($data['slider_id'] ?? uniqid());
    $height   = $data['height'] ?? '500px';
    $autoplay = !empty($data['autoplay']) ? 'carousel ' : '';
    $interval = (int) ($data['interval'] ?? 5000);
    $controls = !empty($data['show_controls']);
    $indicators = !empty($data['show_indicators']);
    $items    = $data['items'] ?? [];
@endphp

<div id="{{ $sliderId }}"
     class="carousel {{ $autoplay }}slide catmin-slider-fullwidth"
     data-bs-ride="{{ !empty($data['autoplay']) ? 'carousel' : 'false' }}"
     data-bs-interval="{{ $interval }}"
     style="width:100%;background:#000;">

    @if($indicators && count($items) > 1)
        <div class="carousel-indicators">
            @foreach($items as $i => $item)
                <button type="button"
                    data-bs-target="#{{ $sliderId }}"
                    data-bs-slide-to="{{ $i }}"
                    @class(['active' => $i === 0])
                    @if($i === 0) aria-current="true" @endif
                    aria-label="Slide {{ $i + 1 }}">
                </button>
            @endforeach
        </div>
    @endif

    <div class="carousel-inner">
        @foreach($items as $i => $item)
            <div class="carousel-item @if($i === 0) active @endif"
                 style="height:{{ $height }};overflow:hidden;">
                @if(!empty($item['image_url']))
                    <img src="{{ $item['image_url'] }}"
                         class="d-block w-100 h-100"
                         style="object-fit:cover;"
                         alt="{{ $item['title'] ?? '' }}"
                         loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                @else
                    <div class="d-block w-100 h-100" style="background:#1a1a2e;"></div>
                @endif

                @if(!empty($item['title']) || !empty($item['subtitle']) || !empty($item['cta_label']))
                    <div class="carousel-caption d-none d-md-block">
                        @if(!empty($item['title']))
                            <h5>{{ $item['title'] }}</h5>
                        @endif
                        @if(!empty($item['subtitle']))
                            <p>{{ $item['subtitle'] }}</p>
                        @endif
                        @if(!empty($item['cta_label']) && !empty($item['cta_url']))
                            <a href="{{ $item['cta_url'] }}" class="btn btn-light btn-sm">{{ $item['cta_label'] }}</a>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($controls && count($items) > 1)
        <button class="carousel-control-prev" type="button"
                data-bs-target="#{{ $sliderId }}" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Précédent</span>
        </button>
        <button class="carousel-control-next" type="button"
                data-bs-target="#{{ $sliderId }}" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Suivant</span>
        </button>
    @endif
</div>
