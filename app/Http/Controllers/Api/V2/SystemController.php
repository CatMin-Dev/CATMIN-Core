<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\V2Response;
use App\Services\HealthCheckService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        $health = HealthCheckService::run();

        return V2Response::success($health, [
            'resource' => 'health',
        ], $health['ok'] ? 200 : 503);
    }

    public function version(): JsonResponse
    {
        return V2Response::success([
            'catmin_version' => (string) SettingService::get('system.catmin_version', 'v2-dev'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ], [
            'resource' => 'version',
        ]);
    }

    public function status(): JsonResponse
    {
        return V2Response::success([
            'app_name' => (string) config('app.name', 'CATMIN'),
            'environment' => app()->environment(),
            'server_time' => now()->toIso8601String(),
            'api_key_id' => request()->attributes->get('catmin_api_key_id'),
            'api_key_name' => request()->attributes->get('catmin_api_key_name'),
        ], [
            'resource' => 'status',
        ]);
    }
}
