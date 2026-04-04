@extends('frontend.layouts.base')

@push('head_css')
    <link rel="stylesheet" href="{{ config('catmin_frontend.leaflet_css') }}" crossorigin="anonymous">
@endpush

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-12 col-lg-11">

            <nav aria-label="Fil d'Ariane" class="mb-4">
                <ol class="breadcrumb cf-breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('frontend.home') }}">Accueil</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Carte</li>
                </ol>
            </nav>

            <h1 class="h3 mb-4">Carte des localisations</h1>

            <div id="cf-public-map" role="application" aria-label="Carte interactive des localisations"></div>

        </div>
    </div>

</div>
@endsection

@push('foot_js')
    <script src="{{ config('catmin_frontend.leaflet_js') }}" crossorigin="anonymous"></script>
    <script>
    (function () {
        var geoData = {!! $geoJson !!};
        var cfg     = @json($mapConfig);

        var map = L.map('cf-public-map').setView([cfg.lat, cfg.lng], cfg.zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        L.geoJSON(geoData, {
            pointToLayer: function (feature, latlng) {
                var color = (feature.properties && feature.properties.color) || '#0d6efd';
                var icon  = L.divIcon({
                    className: '',
                    html: '<div style="width:14px;height:14px;border-radius:50%;background:' + color + ';border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>',
                    iconSize:  [14, 14],
                    iconAnchor:[7, 7],
                });
                return L.marker(latlng, {icon: icon});
            },
            onEachFeature: function (feature, layer) {
                var p = feature.properties || {};
                var html = '<strong>' + (p.name || '') + '</strong>';
                if (p.address)  html += '<br><small>' + p.address + '</small>';
                if (p.category) html += '<br><span style="color:#888;font-size:.8em">' + p.category + '</span>';
                if (p.phone)    html += '<br><a href="tel:' + p.phone + '">' + p.phone + '</a>';
                if (p.website)  html += '<br><a href="' + p.website + '" target="_blank" rel="noopener">Site web</a>';
                layer.bindPopup(html);
            },
        }).addTo(map);
    })();
    </script>
@endpush
