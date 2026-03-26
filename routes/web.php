<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\LegacyPreviewController;
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

// ========== PUBLIC / LEGACY ROUTES ==========

Route::get('/', function () {
    return redirect('/frontend/index.php');
});

Route::get('/dashboard', function () {
    return redirect('/dashboard/index.php?page=dashboard');
});

Route::get('/dashboard/{page}', function (string $page) {
    $allowedPages = config('catmin.dashboard.pages_whitelist');

    $sanitizedPage = strtolower($page);

    if (!preg_match('/^[a-z0-9_\-]+$/', $sanitizedPage) || !in_array($sanitizedPage, $allowedPages, true)) {
        $sanitizedPage = 'dashboard';
    }

    return redirect('/dashboard/index.php?page=' . $sanitizedPage);
});

// ========== ADMIN ROUTES ==========

$adminConfig = config('catmin.admin');
$adminPath = $adminConfig['path'];
$adminMiddleware = $adminConfig['middleware'];

Route::prefix($adminPath)->middleware($adminMiddleware)->name('admin.')->group(function () {
    
    // Public admin routes (no auth required)
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->withoutMiddleware('catmin.admin')
        ->name('login');
    
    Route::post('/login', [AuthController::class, 'login'])
        ->withoutMiddleware('catmin.admin')
        ->name('login.submit');
    
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // Admin bridge (temporary debug view)
    Route::view('/bridge', 'admin.bridge')
        ->name('bridge');

    // Legacy content preview (authenticated)
    Route::get('/preview/{page?}', LegacyPreviewController::class)
        ->name('preview');

    // Dashboard/Home (authenticated)
    Route::get('/access', function () {
        return redirect('/dashboard/index.php?page=dashboard');
    })->name('access');

    // Error pages
    Route::get('/errors/403', fn () => redirect('/dashboard/page_403.html'))
        ->name('error.403');
    Route::get('/errors/404', fn () => redirect('/dashboard/page_404.html'))
        ->name('error.404');
    Route::get('/errors/500', fn () => redirect('/dashboard/page_500.html'))
        ->name('error.500');
});
