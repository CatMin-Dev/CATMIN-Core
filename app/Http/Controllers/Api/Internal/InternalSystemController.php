<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class InternalSystemController extends Controller
{
    /**
     * GET /api/internal/system/status (protected)
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => config('app.name'),
                'environment' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/internal/system/version (protected)
     */
    public function version(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'catmin_version' => (string) SettingService::get('system.catmin_version', 'v1-dev'),
                'laravel_version' => app()->version(),
            ],
        ]);
    }
}
