<?php

namespace Modules\Cache\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Cache\Services\CacheAdminService;

class CacheController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('modules/Cache/Views/index.blade.php'), [
            'currentPage' => 'cache',
            'info' => CacheAdminService::info(),
            'entryCount' => CacheAdminService::cacheEntryCount(),
            'queryCache' => CacheAdminService::queryCacheStats(),
        ]);
    }

    public function clearAll(): RedirectResponse
    {
        $results = CacheAdminService::clearAll();
        $cleared = collect($results)->filter()->count();
        $total = count($results);

        return redirect()->route('admin.cache.index')
            ->with('success', "Cache vidé: {$cleared}/{$total} couche(s) nettoyée(s).");
    }

    public function clearSettings(): RedirectResponse
    {
        CacheAdminService::clearSettings();

        return redirect()->route('admin.cache.index')
            ->with('success', 'Cache settings réinitialisé.');
    }

    public function clearViews(): RedirectResponse
    {
        CacheAdminService::clearViews();

        return redirect()->route('admin.cache.index')
            ->with('success', 'Cache des vues Blade vidé.');
    }

    public function clearQueryCache(): RedirectResponse
    {
        $count = CacheAdminService::clearQueryCache();

        return redirect()->route('admin.cache.index')
            ->with('success', 'Cache requêtes vidé. Clés supprimées: ' . $count . '.');
    }
}
