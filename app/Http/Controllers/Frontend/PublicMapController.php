<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use App\Services\AddonManager;
use Illuminate\View\View;

/**
 * Public map page — renders interactive Leaflet/OSM map
 * from catmin-map addon data.
 * Returns 404 if the addon is absent or disabled.
 */
class PublicMapController extends Controller
{
    public function __construct(
        private readonly FrontendResolverService $resolver,
    ) {}

    public function __invoke(): View
    {
        // Only available when catmin-map addon is active
        $addonEnabled = class_exists(\Addons\CatminMap\Models\GeoLocation::class)
            && \Illuminate\Support\Facades\Schema::hasTable('geo_locations');

        abort_unless($addonEnabled && config('catmin_frontend.map_enabled', true), 404);

        $siteName = $this->resolver->siteName();
        $seo = $this->resolver->seo(null, null, [
            'title'   => 'Carte – ' . $siteName,
            'og_type' => 'website',
        ]);

        // Build GeoJSON inline for the Leaflet map
        $points = \Addons\CatminMap\Models\GeoLocation::query()
            ->where('status', 'published')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('category')
            ->get()
            ->map(function ($loc): array {
                return [
                    'type'     => 'Feature',
                    'geometry' => [
                        'type'        => 'Point',
                        'coordinates' => [(float) $loc->lng, (float) $loc->lat],
                    ],
                    'properties' => [
                        'name'    => (string) $loc->name,
                        'address' => $loc->fullAddress(),
                        'phone'   => (string) ($loc->phone ?? ''),
                        'website' => (string) ($loc->website ?? ''),
                        'category' => $loc->category?->name ?? '',
                        'color'    => $loc->category?->color ?? '#0d6efd',
                    ],
                ];
            });

        return view('frontend.map.index', [
            'siteName'    => $siteName,
            'seo'         => $seo,
            'primaryMenu' => $this->resolver->menu('primary'),
            'geoJson'     => json_encode(['type' => 'FeatureCollection', 'features' => $points], JSON_UNESCAPED_UNICODE),
            'mapConfig'   => [
                'lat'  => (float) config('catmin_frontend.map_default_lat', 48.8566),
                'lng'  => (float) config('catmin_frontend.map_default_lng', 2.3522),
                'zoom' => (int) config('catmin_frontend.map_default_zoom', 6),
            ],
        ]);
    }
}
