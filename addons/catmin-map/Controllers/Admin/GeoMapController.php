<?php

namespace Addons\CatminMap\Controllers\Admin;

use Addons\CatminMap\Services\GeoAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeoMapController extends Controller
{
    public function __construct(private readonly GeoAdminService $service) {}

    public function index(Request $request): View
    {
        $categoryId = (int) $request->get('category_id', 0);
        $categories = $this->service->categories();
        $points     = $this->service->mapPoints($categoryId);

        return view()->file(
            base_path('addons/catmin-map/Views/map/index.blade.php'),
            compact('categories', 'points', 'categoryId')
        );
    }
}
