<?php

namespace Addons\CatminMap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoCategory extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'description',
        'active',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'active' => 'boolean',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(GeoLocation::class, 'geo_category_id');
    }
}
