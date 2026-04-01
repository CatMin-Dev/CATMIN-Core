<?php

namespace App\Http\Controllers;

use App\Services\InstallCheckService;
use App\Services\InstallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    protected InstallService $installService;

    public function __construct(InstallService $installService)
    {
        $this->installService = $installService;
    }

    /**
     * Show install welcome screen
     */
    public function index()
    {
        // If already installed, redirect to admin
        if (InstallService::isInstalled()) {
            return redirect('/admin/dashboard');
        }

        $status = $this->installService->getInstallationStatus();

        return view('install.index', [
            'status' => $status,
            'step' => 'welcome',
        ]);
    }

    /**
     * Show system requirements check
     */
    public function systemCheck()
    {
        if (InstallService::isInstalled()) {
            return redirect('/admin/dashboard');
        }

        $checks = InstallCheckService::run();

        return view('install.system-check', [
            'checks' => $checks,
            'step' => 'system',
        ]);
    }

    /**
     * Show database configuration form
     */
    public function databaseForm()
    {
        if (InstallService::isInstalled()) {
            return redirect('/admin/dashboard');
        }

        return view('install.database-config', [
            'step' => 'database',
            'currentConfig' => [
                'DB_HOST' => env('DB_HOST', 'localhost'),
                'DB_PORT' => env('DB_PORT', 3306),
                'DB_DATABASE' => env('DB_DATABASE', ''),
                'DB_USERNAME' => env('DB_USERNAME', ''),
            ],
        ]);
    }

    /**
     * Test database connection
     */
    public function testDatabase(Request $request)
    {
        $validated = $request->validate([
            'DB_HOST' => 'required|string',
            'DB_PORT' => 'required|integer|min:1|max:65535',
            'DB_DATABASE' => 'required|string',
            'DB_USERNAME' => 'required|string',
            'DB_PASSWORD' => 'required|string',
            'DB_CONNECTION' => 'required|in:mysql,postgres,sqlite',
        ]);

        $result = $this->installService->testDatabaseConnection($validated);

        return response()->json($result);
    }

    /**
     * Configure database
     */
    public function configureDatabase(Request $request)
    {
        $validated = $request->validate([
            'DB_HOST' => 'required|string',
            'DB_PORT' => 'required|integer|min:1|max:65535',
            'DB_DATABASE' => 'required|string',
            'DB_USERNAME' => 'required|string',
            'DB_PASSWORD' => 'required|string',
            'DB_CONNECTION' => 'required|in:mysql,postgres,sqlite',
        ]);

        // Update .env
        $updated = $this->installService->updateEnvFile([
            'DB_HOST' => $validated['DB_HOST'],
            'DB_PORT' => $validated['DB_PORT'],
            'DB_DATABASE' => $validated['DB_DATABASE'],
            'DB_USERNAME' => $validated['DB_USERNAME'],
            'DB_PASSWORD' => $validated['DB_PASSWORD'],
            'DB_CONNECTION' => $validated['DB_CONNECTION'],
        ]);

        if (!$updated) {
            return response()->json([
                'ok' => false,
                'message' => 'Impossible de mettre à jour .env',
            ], 422);
        }

        // Clear cached config
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        return response()->json([
            'ok' => true,
            'message' => 'Configuration base de données sauvegardée.',
        ]);
    }

    /**
     * Show admin user creation form
     */
    public function adminForm()
    {
        if (InstallService::isInstalled()) {
            return redirect('/admin/dashboard');
        }

        try {
            // Check if migrations are needed
            $adminTableExists = DB::table('information_schema.tables')
                ->where('table_schema', env('DB_DATABASE'))
                ->where('table_name', 'admin_users')
                ->exists();

            if (!$adminTableExists) {
                // Run migrations first
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $this->installService->seedRolesAndPermissions();
            }
        } catch (\Throwable $e) {
            return view('install.admin-create', [
                'step' => 'admin',
                'error' => 'Migrations non complétées - veuillez comprendre les logs.',
            ]);
        }

        return view('install.admin-create', [
            'step' => 'admin',
        ]);
    }

    /**
     * Create admin user
     */
    public function createAdmin(Request $request)
    {
        if (InstallService::isInstalled()) {
            return redirect('/admin/dashboard');
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:admin_users,email',
            'password' => 'required|string|min:12|confirmed',
        ]);

        $result = $this->installService->createAdminUser($validated);

        if (!$result['ok']) {
            return back()->withErrors(['general' => $result['message']]);
        }

        return redirect('/install/complete');
    }

    /**
     * Show installation completion screen
     */
    public function complete()
    {
        if (InstallService::isInstalled()) {
            // Mark as complete
            $this->installService->markInstallationComplete();
            return view('install.complete', [
                'step' => 'complete',
            ]);
        }

        return redirect('/install');
    }

    /**
     * Finalize installation
     */
    public function finalize()
    {
        try {
            // Compile configcache
            $this->installService->compileConfig();

            // Mark installation as complete
            $this->installService->markInstallationComplete();

            return response()->json([
                'ok' => true,
                'message' => 'Installation complète!',
                'redirect' => '/admin/login',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Erreur finalisation: ' . $e->getMessage(),
            ], 500);
        }
    }
}
