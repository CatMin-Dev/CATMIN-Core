<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ModuleLoader;
use App\Services\ModuleManager;
use App\Services\ModuleMigrationRunner;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use stdClass;

class DashboardController extends Controller
{
    public function index(): View
    {
        $allModules = ModuleManager::all();
        $enabledModules = ModuleManager::enabled();
        $siteName = (string) SettingService::get('site.name', 'CATMIN');
        $siteUrl = (string) SettingService::get('site.url', config('app.url'));

        return view('admin.pages.dashboard', [
            'currentPage' => 'dashboard',
            'welcome' => [
                'site_name' => $siteName,
                'site_url' => $siteUrl,
                'admin_user' => (string) session('catmin_admin_username', config('catmin.admin.username')),
            ],
            'stats' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'settings' => Setting::count(),
                'modules_enabled' => $enabledModules->count(),
                'modules_total' => $allModules->count(),
            ],
            'systemInfo' => [
                'catmin_version' => (string) SettingService::get('system.catmin_version', 'v1-dev'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'environment' => app()->environment(),
                'admin_path' => (string) config('catmin.admin.path', 'admin'),
            ],
            'enabledModules' => $enabledModules->take(8)->values(),
            'recentUsers' => User::with('roles')->latest()->limit(5)->get(),
            'contentModules' => collect(['pages', 'articles', 'media', 'menus', 'blocks'])
                ->map(fn (string $slug) => ModuleManager::find($slug))
                ->filter()
                ->values(),
        ]);
    }

    public function users(): RedirectResponse
    {
        return redirect()->route('admin.users.manage');
    }

    public function roles(): RedirectResponse
    {
        return redirect()->route('admin.roles.manage');
    }

    public function settings(): RedirectResponse
    {
        return redirect()->route('admin.settings.manage');
    }

    public function modules(): View
    {
        $allModules = ModuleManager::all();

        // Build per-module migration info based on real migration files.
        $migrationInfo = [];
        $migratableEnabledCount = 0;
        foreach ($allModules as $module) {
            $slug = (string) $module->slug;
            $migrationsPath = base_path('modules/' . $module->directory . '/Migrations');
            $hasMigrationsFolder = is_dir($migrationsPath);
            $migrationFilesCount = $hasMigrationsFolder
                ? (int) count(glob($migrationsPath . '/*.php') ?: [])
                : 0;
            $hasMigrations = $migrationFilesCount > 0;
            $installedVersion = ModuleMigrationRunner::getInstalledVersion($slug);
            $currentVersion = (string) ($module->version ?? '');

            if ($module->enabled && $hasMigrations) {
                $migratableEnabledCount++;
            }

            $migrationInfo[$slug] = [
                'has_migrations' => $hasMigrations,
                'migrations_count' => $migrationFilesCount,
                'installed_version' => $installedVersion,
                'has_upgrade' => $hasMigrations && $installedVersion !== '' && $installedVersion !== $currentVersion,
                'never_migrated' => $hasMigrations && $installedVersion === '',
            ];
        }

        return view('admin.pages.modules.index', [
            'currentPage' => 'modules',
            'modules' => $allModules,
            'stateIssues' => ModuleManager::stateIssues(),
            'routesInfo' => ModuleLoader::getRoutesInfo(),
            'migrationInfo' => $migrationInfo,
            'migratableEnabledCount' => $migratableEnabledCount,
        ]);
    }

    public function migrateEnabledModules(Request $request): JsonResponse|RedirectResponse
    {
        $migrated = 0;
        $skipped = 0;
        $failed = [];

        foreach (ModuleManager::enabled() as $module) {
            $slug = (string) $module->slug;
            $migrationsPath = base_path('modules/' . $module->directory . '/Migrations');
            $hasMigrations = is_dir($migrationsPath) && count(glob($migrationsPath . '/*.php') ?: []) > 0;

            if (!$hasMigrations) {
                $skipped++;
                continue;
            }

            try {
                ModuleMigrationRunner::runForModule($slug);
                $migrated++;
            } catch (\Throwable $e) {
                $failed[] = $slug . ': ' . $e->getMessage();
            }
        }

        $message = "Migrations globales terminées: {$migrated} module(s) traité(s), {$skipped} ignoré(s).";

        if (!empty($failed)) {
            $message .= ' Erreurs: ' . implode(' | ', $failed);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => empty($failed),
                'message' => $message,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'failed' => $failed,
            ], empty($failed) ? 200 : 500);
        }

        return redirect()->back()->with(empty($failed) ? 'success' : 'error', $message);
    }

    public function content(string $module): View|RedirectResponse
    {
        if ($module === 'pages') {
            return redirect()->route('admin.pages.manage');
        }

        if ($module === 'articles') {
            return redirect()->route('admin.articles.manage');
        }

        if ($module === 'media') {
            return redirect()->route('admin.media.manage');
        }

        if ($module === 'menus') {
            return redirect()->route('admin.menus.manage');
        }

        if ($module === 'blocks') {
            return redirect()->route('admin.blocks.manage');
        }

        $moduleConfig = ModuleManager::find($module);

        abort_if(!$moduleConfig instanceof stdClass, 404);

        return view('admin.pages.content.show', [
            'currentPage' => 'content-' . $module,
            'module' => $moduleConfig,
        ]);
    }

    public function enableModule(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        try {
            $module = ModuleManager::find($slug);
            abort_if(!$module instanceof stdClass, 404);

            $validation = ModuleManager::canEnable($slug);
            if (!$validation['allowed']) {
                $message = $validation['message'];
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->back()->with('error', $message);
            }

            $updated = ModuleManager::enable($slug);
            if (!$updated) {
                $message = "Erreur lors de l'activation du module {$module->name}.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 500);
                }
                return redirect()->back()->with('error', $message);
            }

            $message = "Module {$module->name} activé avec succès.";
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Erreur lors de l\'activation du module: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->back()->with('error', $message);
        }
    }

    public function migrateModule(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        try {
            $module = ModuleManager::find($slug);
            abort_if(!$module instanceof stdClass, 404);

            if (!ModuleManager::isEnabled($slug)) {
                $message = "Impossible de migrer {$module->name}: module non actif.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->back()->with('error', $message);
            }

            $result = ModuleMigrationRunner::runForModule($slug);

            $message = $result['ran'] > 0
                ? "Module {$module->name}: {$result['ran']} migration(s) appliquée(s)."
                : "Module {$module->name}: déjà à jour, aucune migration en attente.";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'output' => $result['output']]);
            }
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Erreur migration: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->back()->with('error', $message);
        }
    }

    public function disableModule(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        try {
            $module = ModuleManager::find($slug);
            abort_if(!$module instanceof stdClass, 404);

            $validation = ModuleManager::canDisable($slug);
            if (!$validation['allowed']) {
                $message = $validation['message'];
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->back()->with('error', $message);
            }

            $updated = ModuleManager::disable($slug);
            if (!$updated) {
                $message = "Erreur lors de la désactivation du module {$module->name}.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 500);
                }
                return redirect()->back()->with('error', $message);
            }

            $message = "Module {$module->name} désactivé avec succès.";
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Erreur lors de la désactivation du module: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return redirect()->back()->with('error', $message);
        }
    }
}