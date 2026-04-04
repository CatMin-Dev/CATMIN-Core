<?php

namespace App\Http\Controllers\Admin\Addons;

use App\Http\Controllers\Controller;
use App\Services\Addons\AddonBundleInstaller;
use App\Services\Addons\AddonBundleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddonBundleController extends Controller
{
    public function __construct(
        private readonly AddonBundleService $bundleService,
        private readonly AddonBundleInstaller $bundleInstaller,
    ) {
    }

    public function index(): View
    {
        return view('admin.pages.addons.bundles', [
            'currentPage' => 'addons-bundles',
            'bundles' => $this->bundleService->list(),
            'bundleState' => $this->bundleInstaller->state(),
        ]);
    }

    public function install(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:120'],
        ]);

        $result = $this->bundleInstaller->install((string) $validated['slug']);

        return redirect()->route('admin.addons.bundles.index')
            ->with(($result['ok'] ?? false) ? 'success' : 'error', (string) ($result['message'] ?? 'Operation terminee.'));
    }
}
