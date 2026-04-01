<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AddonDistributionService;
use App\Services\AddonManager;
use App\Services\AddonMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddonMarketplaceController extends Controller
{
    public function __construct(private readonly AddonDistributionService $addonDistributionService)
    {
    }

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

    public function install(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_file' => ['required', 'string'],
        ]);

        $packageFile = basename((string) $validated['package_file']);
        $packagePath = AddonMarketplaceService::packagesPath() . '/' . $packageFile;

        $result = $this->addonDistributionService->installPackage($packagePath, true, true);

        AddonMarketplaceService::buildIndex();

        return redirect()->route('admin.addons.marketplace.index')
            ->with(($result['ok'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Operation terminee.'));
    }

    public function enable(Request $request): RedirectResponse
    {
        return $this->setState($request, true);
    }

    public function disable(Request $request): RedirectResponse
    {
        return $this->setState($request, false);
    }

    private function setState(Request $request, bool $enabled): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string'],
        ]);

        $slug = (string) $validated['slug'];
        $ok = $enabled ? AddonManager::enable($slug) : AddonManager::disable($slug);

        AddonMarketplaceService::buildIndex();

        return redirect()->route('admin.addons.marketplace.index')
            ->with($ok ? 'success' : 'error', $ok ? 'Etat addon mis a jour.' : 'Impossible de changer l etat addon.');
    }
}
