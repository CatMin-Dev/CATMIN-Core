<?php

use Illuminate\Support\Facades\Route;

// Example addon routes (disabled by default via addon.json).
Route::middleware(['web'])
    ->get('/addon-example/ping', function () {
        return response()->json([
            'ok' => true,
            'addon' => 'example-addon',
        ]);
    });
