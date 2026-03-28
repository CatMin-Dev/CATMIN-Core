<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AddonMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AddonMarketplaceController extends Controller
{
    public function index(): View
    {
        $index = AddonMarketplaceService::readIndex();

        return view('admin.pages.addons.marketplace', [
            'currentPage' => 'addons-marketplace',
            'index' => $index,
        ]);
    }

    public function rebuild(): RedirectResponse
    {
        AddonMarketplaceService::buildIndex();

        return redirect()->route('admin.addons.marketplace.index')
            ->with('success', 'Index marketplace addons reconstruit.');
    }
}
