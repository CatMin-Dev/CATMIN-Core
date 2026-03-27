<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class InternalSettingsController extends Controller
{
    /**
     * GET /api/internal/settings/public
     *
     * Returns only settings marked as is_public=true.
     */
    public function publicSettings(): JsonResponse
    {
        $settings = Setting::query()
            ->where('is_public', true)
            ->orderBy('key')
            ->get(['key', 'value', 'type', 'group'])
            ->map(fn (Setting $s) => [
                'key' => $s->key,
                'value' => $s->value,
                'type' => $s->type,
                'group' => $s->group,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $settings,
            'meta' => [
                'count' => $settings->count(),
                'scope' => 'public-settings',
            ],
        ]);
    }
}
