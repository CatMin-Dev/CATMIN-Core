<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AddonMarketplaceController;
use App\Http\Controllers\Admin\AdminPasswordResetController;
use App\Http\Controllers\Admin\AdminSessionsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ========== PUBLIC ROUTES ==========

Route::middleware('catmin.frontend.available')->group(function (): void {
    Route::get('/', HomeController::class)
        ->name('frontend.root');

    Route::get('/' . config('catmin.frontend.path', 'site'), HomeController::class)
        ->name('frontend.home');

    Route::get('/page/{slug}', PageController::class)
        ->where('slug', '[A-Za-z0-9\-\/]+')
        ->name('frontend.page');
});

// ========== ADMIN ROUTES ==========

$adminConfig = config('catmin.admin');
$adminPath = $adminConfig['path'];
$adminMiddleware = $adminConfig['middleware'];

Route::prefix($adminPath)->middleware('web')->name('admin.')->group(function () use ($adminMiddleware) {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:catmin-login')
        ->name('login.submit');

    Route::get('/forgot-password', [AdminPasswordResetController::class, 'showRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [AdminPasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:catmin-password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AdminPasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [AdminPasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:catmin-password-reset')
        ->name('password.update');

    // 2FA routes — accessibles sans auth complète mais seulement si pending
    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerify'])
        ->name('2fa.verify');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])
        ->name('2fa.verify.submit');

    Route::middleware($adminMiddleware)->group(function () {
        Route::get('/2fa/setup', [TwoFactorController::class, 'showSetup'])
            ->name('2fa.setup');
        Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])
            ->name('2fa.enable');
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])
            ->name('2fa.disable');
        Route::post('/2fa/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])
            ->name('2fa.recovery.regenerate');

        Route::get('/sessions', [AdminSessionsController::class, 'index'])
            ->middleware('catmin.permission:module.core.list')
            ->name('sessions.index');
        Route::post('/sessions/revoke', [AdminSessionsController::class, 'revoke'])
            ->middleware('catmin.permission:module.core.config')
            ->name('sessions.revoke');
        Route::post('/sessions/revoke-others', [AdminSessionsController::class, 'revokeOthers'])
            ->middleware('catmin.permission:module.core.config')
            ->name('sessions.revoke-others');

        Route::get('/', [DashboardController::class, 'index'])
            ->name('index');

        Route::get('/access', function () {
            return redirect()->route('admin.index');
        })->name('access');

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::get('/users', [DashboardController::class, 'users'])
            ->middleware('catmin.permission:module.users.list')
            ->name('users.index');

        Route::get('/roles', [DashboardController::class, 'roles'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.index');

        Route::get('/settings', [DashboardController::class, 'settings'])
            ->middleware('catmin.permission:module.settings.list')
            ->name('settings.index');

        Route::get('/modules', [DashboardController::class, 'modules'])
            ->middleware('catmin.permission:module.core.list')
            ->name('modules.index');

        Route::get('/addons/marketplace', [AddonMarketplaceController::class, 'index'])
            ->middleware('catmin.permission:module.core.config')
            ->name('addons.marketplace.index');

        Route::post('/addons/marketplace/rebuild', [AddonMarketplaceController::class, 'rebuild'])
            ->middleware('catmin.permission:module.core.config')
            ->name('addons.marketplace.rebuild');

        Route::get('/modules/{slug}/config', [DashboardController::class, 'moduleConfig'])
            ->middleware('catmin.permission:module.core.config')
            ->name('modules.config');

        Route::post('/modules/{slug}/config', [DashboardController::class, 'updateModuleConfig'])
            ->middleware('catmin.permission:module.core.config')
            ->name('modules.config.update');

        Route::post('/modules/{slug}/enable', [DashboardController::class, 'enableModule'])
            ->middleware('catmin.permission:module.core.config')
            ->name('modules.enable');

        Route::post('/modules/{slug}/disable', [DashboardController::class, 'disableModule'])
            ->middleware('catmin.permission:module.core.config')
            ->name('modules.disable');

        Route::post('/modules/{slug}/migrate', [DashboardController::class, 'migrateModule'])
            ->middleware('catmin.permission:module.core.config')
            ->name('modules.migrate');

        Route::post('/modules/migrate-enabled', [DashboardController::class, 'migrateEnabledModules'])
            ->middleware('catmin.permission:module.core.config')
            ->name('modules.migrate-enabled');

        Route::get('/content/{module}', [DashboardController::class, 'content'])
            ->whereIn('module', ['pages', 'articles', 'media', 'menus', 'blocks'])
            ->name('content.show');

        Route::view('/errors/403', 'admin.pages.errors.403', ['currentPage' => null])
            ->name('error.403');
        Route::view('/errors/404', 'admin.pages.errors.404', ['currentPage' => null])
            ->name('error.404');
        Route::view('/errors/500', 'admin.pages.errors.500', ['currentPage' => null])
            ->name('error.500');
    });
});

// Public error pages (not under /admin prefix)
Route::get('/admin-error/403', fn () => view('admin.pages.errors.403', ['currentPage' => null]))
    ->name('error.403.blade');
Route::get('/admin-error/404', fn () => view('admin.pages.errors.404', ['currentPage' => null]))
    ->name('error.404.blade');
Route::get('/admin-error/500', fn () => view('admin.pages.errors.500', ['currentPage' => null]))
    ->name('error.500.blade');

