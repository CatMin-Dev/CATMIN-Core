<?php

namespace App\Services\Api;

use Illuminate\Http\JsonResponse;

class V2Response
{
    /**
     * @param array<string, mixed> $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => array_merge([
                'api_version' => 'v2',
                'timestamp' => now()->toIso8601String(),
            ], $meta),
        ], $status);
    }

    /**
     * @param array<string, mixed> $details
     * @param array<string, mixed> $meta
     */
    public static function error(string $code, string $message, int $status, array $details = [], array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => array_merge([
                'api_version' => 'v2',
                'timestamp' => now()->toIso8601String(),
            ], $meta),
        ], $status);
    }
}
