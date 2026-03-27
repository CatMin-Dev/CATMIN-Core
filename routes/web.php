<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
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

Route::get('/', HomeController::class)
    ->name('frontend.root');

Route::get('/' . config('catmin.frontend.path', 'site'), HomeController::class)
    ->name('frontend.home');

Route::get('/page/{slug}', PageController::class)
    ->where('slug', '[A-Za-z0-9\-\/]+')
    ->name('frontend.page');

// ========== ADMIN ROUTES ==========

$adminConfig = config('catmin.admin');
$adminPath = $adminConfig['path'];
$adminMiddleware = $adminConfig['middleware'];

Route::prefix($adminPath)->middleware('web')->name('admin.')->group(function () use ($adminMiddleware) {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.submit');

    Route::middleware($adminMiddleware)->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->name('index');

        Route::get('/access', function () {
            return redirect()->route('admin.index');
        })->name('access');

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

        Route::get('/users', [DashboardController::class, 'users'])
            ->name('users.index');

        Route::get('/roles', [DashboardController::class, 'roles'])
            ->name('roles.index');

        Route::get('/settings', [DashboardController::class, 'settings'])
            ->name('settings.index');

        Route::get('/modules', [DashboardController::class, 'modules'])
            ->name('modules.index');

        Route::get('/content/{module}', [DashboardController::class, 'content'])
            ->whereIn('module', ['pages', 'blog', 'news', 'media'])
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

