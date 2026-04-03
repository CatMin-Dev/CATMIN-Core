<?php

namespace Addons\CatminMap\Controllers\Admin;

use Addons\CatminMap\Models\GeoLocation;
use Addons\CatminMap\Services\GeoAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeoLocationController extends Controller
{
    public function __construct(private readonly GeoAdminService $service) {}

    public function index(Request $request): View
    {
        $locations  = $this->service->locations($request->only(['q', 'category_id', 'city', 'status']));
        $categories = $this->service->categories();

        return view()->file(
            base_path('addons/catmin-map/Views/locations/index.blade.php'),
            compact('locations', 'categories')
        );
    }

    public function create(): View
    {
        $categories = $this->service->categories();

        return view()->file(
            base_path('addons/catmin-map/Views/locations/edit.blade.php'),
            ['location' => null, 'categories' => $categories]
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => 'required|string|max:191',
            'lat'     => 'nullable|numeric|between:-90,90',
            'lng'     => 'nullable|numeric|between:-180,180',
            'email'   => 'nullable|email|max:191',
            'website' => 'nullable|url|max:255',
            'status'  => 'required|in:published,draft,archived',
        ]);

        $location = $this->service->createLocation($request->all());

        return redirect()->route('admin.map.locations.edit', $location)
            ->with('success', 'Lieu créé.');
    }

    public function edit(GeoLocation $geoLocation): View
    {
        $categories = $this->service->categories();

        return view()->file(
            base_path('addons/catmin-map/Views/locations/edit.blade.php'),
            ['location' => $geoLocation->load('category'), 'categories' => $categories]
        );
    }

    public function update(Request $request, GeoLocation $geoLocation): RedirectResponse
    {
        $request->validate([
            'name'    => 'required|string|max:191',
            'lat'     => 'nullable|numeric|between:-90,90',
            'lng'     => 'nullable|numeric|between:-180,180',
            'email'   => 'nullable|email|max:191',
            'website' => 'nullable|url|max:255',
            'status'  => 'required|in:published,draft,archived',
        ]);

        $this->service->updateLocation($geoLocation, $request->all());

        return redirect()->route('admin.map.locations.edit', $geoLocation)
            ->with('success', 'Lieu mis à jour.');
    }

    public function destroy(GeoLocation $geoLocation): RedirectResponse
    {
        $this->service->deleteLocation($geoLocation);

        return redirect()->route('admin.map.locations.index')
            ->with('success', 'Lieu supprimé.');
    }
}
