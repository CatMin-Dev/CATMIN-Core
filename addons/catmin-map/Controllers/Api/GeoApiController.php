<?php

namespace Addons\CatminMap\Controllers\Api;

use Addons\CatminMap\Services\GeoAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoApiController extends Controller
{
    public function __construct(private readonly GeoAdminService $service) {}

    /**
     * Returns GeoJSON FeatureCollection of published locations with coordinates.
     */
    public function points(Request $request): JsonResponse
    {
        $categoryId = (int) $request->get('category_id', 0);
        $points     = $this->service->mapPoints($categoryId);

        $features = $points->map(function ($loc): array {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type'        => 'Point',
                    'coordinates' => [$loc->lng, $loc->lat],
                ],
                'properties' => [
                    'id'          => $loc->id,
                    'name'        => $loc->name,
                    'address'     => $loc->fullAddress(),
                    'city'        => $loc->city,
                    'phone'       => $loc->phone,
                    'website'     => $loc->website,
                    'featured'    => $loc->featured,
                    'category'    => $loc->category ? [
                        'id'    => $loc->category->id,
                        'name'  => $loc->category->name,
                        'color' => $loc->category->color,
                        'icon'  => $loc->category->icon,
                    ] : null,
                    'edit_url' => route('admin.map.locations.edit', $loc->id),
                ],
            ];
        })->values();

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
