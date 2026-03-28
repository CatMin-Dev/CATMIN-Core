<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'value',
        'type',
        'group',
        'description',
        'is_public',
        'is_editable',
        'options',
        'validation_rules',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_editable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(config('catmin.settings.cache_key', 'catmin.settings')));
        static::deleted(fn () => Cache::forget(config('catmin.settings.cache_key', 'catmin.settings')));
    }
}
