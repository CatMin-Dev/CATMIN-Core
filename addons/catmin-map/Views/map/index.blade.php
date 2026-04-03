@extends('admin.layouts.catmin')

@section('page_title', 'Map · Carte interactive')

@section('content')
<header class="catmin-page-header d-flex justify-content-between align-items-end">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-map me-2"></i>Carte interactive</h1>
        <p class="text-muted mb-0">Visualisez tous vos lieux géolocalisés.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.map.locations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list me-1"></i>Liste</a>
        <a href="{{ route('admin.map.locations.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nouveau lieu</a>
    </div>
</header>

<div class="catmin-page-body">
    {{-- Filtres catégories --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.map.index') }}"
           class="btn btn-sm {{ $categoryId === 0 ? 'btn-primary' : 'btn-outline-secondary' }}">
            Tous ({{ $points->count() }})
        </a>
        @foreach($categories as $cat)
            @if($cat->active)
                <a href="{{ route('admin.map.index', ['category_id' => $cat->id]) }}"
                   class="btn btn-sm {{ $categoryId === $cat->id ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <span class="d-inline-block rounded me-1"
                          style="width:10px;height:10px;background:{{ $cat->color }};vertical-align:middle;"></span>
                    {{ $cat->name }}
                </a>
            @endif
        @endforeach
    </div>

    {{-- Map --}}
    <div class="card mb-4">
        <div id="interactiveMap" style="height:600px;"></div>
    </div>

    {{-- Stats --}}
    <div class="row g-3">
        <div class="col-sm-4">
            <div class="card text-center p-3">
                <div class="h2 text-primary mb-0">{{ $points->count() }}</div>
                <div class="text-muted small">Lieux affichés</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center p-3">
                <div class="h2 text-warning mb-0">{{ $points->where('featured', true)->count() }}</div>
                <div class="text-muted small">Mis en avant</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center p-3">
                <div class="h2 text-success mb-0">{{ $categories->where('active', true)->count() }}</div>
                <div class="text-muted small">Catégories actives</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var apiUrl = '{{ route('admin.map.api.points') }}?category_id={{ $categoryId }}';

    var map = L.map('interactiveMap').setView([46.5, 2.3], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    function hexToRgb(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result
            ? { r: parseInt(result[1], 16), g: parseInt(result[2], 16), b: parseInt(result[3], 16) }
            : { r: 59, g: 130, b: 246 };
    }

    function makeIcon(color) {
        var rgb = hexToRgb(color || '#3B82F6');
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 36" width="24" height="36">'
            + '<path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24C24 5.4 18.6 0 12 0z" fill="rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ')"/>'
            + '<circle cx="12" cy="12" r="5" fill="white"/>'
            + '</svg>';
        return L.divIcon({
            html: svg,
            className: '',
            iconSize: [24, 36],
            iconAnchor: [12, 36],
            popupAnchor: [0, -36]
        });
    }

    var markers = L.featureGroup().addTo(map);

    fetch(apiUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (geojson) {
            if (!geojson.features || geojson.features.length === 0) {
                return;
            }
            geojson.features.forEach(function (f) {
                var p   = f.properties;
                var lat = f.geometry.coordinates[1];
                var lng = f.geometry.coordinates[0];
                var color = (p.category && p.category.color) ? p.category.color : '#3B82F6';

                var popupHtml = '<div style="min-width:180px;">'
                    + '<strong>' + p.name + '</strong>'
                    + (p.featured ? ' <span style="color:#f59e0b">★</span>' : '')
                    + (p.address ? '<br><small>' + p.address + '</small>' : '')
                    + (p.phone ? '<br><small>📞 ' + p.phone + '</small>' : '')
                    + (p.website ? '<br><a href="' + p.website + '" target="_blank" rel="noopener">Site web</a>' : '')
                    + '<br><a href="' + p.edit_url + '" class="btn btn-sm btn-outline-secondary mt-2" style="font-size:11px;">Modifier</a>'
                    + '</div>';

                L.marker([lat, lng], { icon: makeIcon(color) })
                    .bindPopup(popupHtml)
                    .addTo(markers);
            });

            if (markers.getLayers().length > 0) {
                map.fitBounds(markers.getBounds().pad(0.1));
            }
        })
        .catch(function (e) { console.error('Map points error:', e); });
})();
</script>
@endpush
@endsection
