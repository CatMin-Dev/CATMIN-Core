<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Dashboard\DashboardKpiService;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\ModuleConfigService;
use App\Services\ModuleLoader;
use App\Services\ModuleManager;
use App\Services\ModuleMigrationRunner;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use stdClass;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardKpiService $dashboardKpiService)
    {
    }

    public function index(): View
    {
        $allModules = ModuleManager::all();
        $enabledModules = ModuleManager::enabled();
        $dashboard = $this->dashboardKpiService->build();
        $moduleWidgets = DashboardWidgetRegistry::collect([
            'dashboard' => $dashboard,
            'enabled_modules' => $enabledModules->pluck('slug')->values()->all(),
        ]);
        $dashboardWidgets = collect($dashboard['widgets'] ?? [])
            ->merge($moduleWidgets)
            ->sortBy([
                ['order', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();

        return view('admin.pages.dashboard', [
            'currentPage' => 'dashboard',
            'dashboard' => $dashboard,
            'dashboardWidgets' => $dashboardWidgets,
            'stats' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'settings' => Setting::count(),
                'modules_enabled' => $enabledModules->count(),
                'modules_total' => $allModules->count(),
            ],
            'systemInfo' => [
                'catmin_version' => (string) config('app.dashboard_version', '2.0.0-dev'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'environment' => app()->environment(),
                'admin_path' => (string) config('catmin.admin.path', 'admin'),
            ],
            'enabledModules' => $enabledModules->take(8)->values(),
        ]);
    }

    public function users(): RedirectResponse
    {
        if (!Route::has('admin.users.manage')) {
            return redirect()->route('admin.index')->with('error', 'Route utilisateurs indisponible: module users inactif.');
        }

        return redirect()->route('admin.users.manage');
    }

    public function roles(): RedirectResponse
    {
        if (!Route::has('admin.roles.manage')) {
            return redirect()->route('admin.index')->with('error', 'Route roles indisponible: module users inactif.');
        }

        return redirect()->route('admin.roles.manage');
    }

    public function settings(): RedirectResponse
    {
        if (!Route::has('admin.settings.manage')) {
            return redirect()->route('admin.index')->with('error', 'Route settings indisponible: module settings inactif.');
        }

        return redirect()->route('admin.settings.manage');
    }

    public function modules(): View
    {
        $allModules = ModuleManager::all();
        $configInfo = [];

        // Build per-module migration info based on real migration files.
        $migrationInfo = [];
        $migratableEnabledCount = 0;
        foreach ($allModules as $module) {
            $slug = (string) $module->slug;
            $configInfo[$slug] = [
                'has_config' => ModuleConfigService::hasConfig($slug),
            ];
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
            'configInfo' => $configInfo,
            'migratableEnabledCount' => $migratableEnabledCount,
        ]);
    }

    public function moduleConfig(string $slug): View|RedirectResponse
    {
        $module = ModuleManager::find($slug);
        abort_if(!$module instanceof stdClass, 404);

        if (!ModuleConfigService::hasConfig($slug)) {
            return redirect()->route('admin.modules.index')->with('error', 'Aucune configuration declarée pour ce module.');
        }

        return view('admin.pages.modules.config', [
            'currentPage' => 'modules',
            'module' => $module,
            'fields' => ModuleConfigService::fields($slug),
            'values' => ModuleConfigService::values($slug),
        ]);
    }

    public function updateModuleConfig(Request $request, string $slug): RedirectResponse
    {
        $module = ModuleManager::find($slug);
        abort_if(!$module instanceof stdClass, 404);

        if (!ModuleConfigService::hasConfig($slug)) {
            return redirect()->route('admin.modules.index')->with('error', 'Aucune configuration declarée pour ce module.');
        }

        $validated = ModuleConfigService::validate($slug, $request->all());
        ModuleConfigService::save($slug, $validated);

        return redirect()->route('admin.modules.config', $slug)->with('success', "Configuration du module {$module->name} mise a jour.");
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

    private function buildWeeklyActivity(): array
    {
        $labels = collect(range(6, 0))
            ->map(fn (int $daysAgo) => Carbon::today()->subDays($daysAgo))
            ->values();

        $fromDate = $labels->first()?->copy()->startOfDay() ?? Carbon::now()->subDays(6)->startOfDay();

        $usersSeries = $this->countByDay('users', 'created_at', $labels, $fromDate);
        $errorsSeries = $this->countErrorLogsByDay($labels, $fromDate);
        $pagesPublishedSeries = $this->countByDay('pages', 'published_at', $labels, $fromDate, fn ($q) => $q->where('status', 'published'));
        $articlesPublishedSeries = $this->countByDay('articles', 'published_at', $labels, $fromDate, fn ($q) => $q->where('status', 'published'));

        $contentSeries = [];
        foreach ($labels as $index => $labelDate) {
            $contentSeries[] = ($pagesPublishedSeries[$index] ?? 0) + ($articlesPublishedSeries[$index] ?? 0);
        }

        return [
            'labels' => $labels->map(fn (Carbon $date) => $date->format('d/m'))->values()->all(),
            'users' => $usersSeries,
            'content' => $contentSeries,
            'errors' => $errorsSeries,
        ];
    }

    private function buildSystemHealth(): array
    {
        $mailerSent = $this->safeCount('mailer_history', function ($query): void {
            $query->where('status', 'sent');
        });

        $mailerFailed = $this->safeCount('mailer_history', function ($query): void {
            $query->where('status', 'failed');
        });

        $recentErrors = $this->safeCount('system_logs', function ($query): void {
            $query->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                ->where('created_at', '>=', now()->subDay());
        });

        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'mailer_sent' => $mailerSent,
            'mailer_failed' => $mailerFailed,
            'recent_errors' => $recentErrors,
            'failed_jobs' => $failedJobs,
        ];
    }

    private function buildContentStatus(): array
    {
        return [
            'pages' => [
                'published' => $this->safeCount('pages', function ($query): void {
                    $query->where('status', 'published');
                }),
                'draft' => $this->safeCount('pages', function ($query): void {
                    $query->where('status', '!=', 'published');
                }),
            ],
            'articles' => [
                'published' => $this->safeCount('articles', function ($query): void {
                    $query->where('status', 'published');
                }),
                'draft' => $this->safeCount('articles', function ($query): void {
                    $query->where('status', '!=', 'published');
                }),
            ],
            'products' => [
                'active' => $this->safeCount('shop_products', function ($query): void {
                    $query->where('status', 'active');
                }),
                'inactive' => $this->safeCount('shop_products', function ($query): void {
                    $query->where('status', '!=', 'active');
                }),
            ],
        ];
    }

    private function countByDay(
        string $table,
        string $column,
        Collection $days,
        Carbon $fromDate,
        ?callable $constraint = null
    ): array {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return $days->map(fn () => 0)->all();
        }

        $query = DB::table($table)
            ->selectRaw("DATE($column) as day, COUNT(*) as total")
            ->whereNotNull($column)
            ->where($column, '>=', $fromDate)
            ->groupBy('day');

        if ($constraint !== null) {
            $constraint($query);
        }

        $counts = $query
            ->pluck('total', 'day')
            ->map(fn ($value) => (int) $value);

        return $days
            ->map(fn (Carbon $date) => (int) ($counts[$date->toDateString()] ?? 0))
            ->all();
    }

    private function countErrorLogsByDay(Collection $days, Carbon $fromDate): array
    {
        if (!Schema::hasTable('system_logs') || !Schema::hasColumn('system_logs', 'created_at') || !Schema::hasColumn('system_logs', 'level')) {
            return $days->map(fn () => 0)->all();
        }

        $counts = DB::table('system_logs')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', $fromDate)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($value) => (int) $value);

        return $days
            ->map(fn (Carbon $date) => (int) ($counts[$date->toDateString()] ?? 0))
            ->all();
    }

    private function safeCount(string $table, ?callable $constraint = null): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($constraint !== null) {
            $constraint($query);
        }

        return (int) $query->count();
    }

}
