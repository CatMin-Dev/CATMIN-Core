<?php

namespace Addons\CatminMap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoLocation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'geo_category_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'country',
        'zip',
        'lat',
        'lng',
        'phone',
        'email',
        'website',
        'opening_hours',
        'status',
        'featured',
        'linked_event_id',
        'linked_shop_id',
        'linked_page_id',
        'metadata',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'lat'             => 'float',
        'lng'             => 'float',
        'featured'        => 'boolean',
        'metadata'        => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(GeoCategory::class, 'geo_category_id');
    }

    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    public function fullAddress(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->zip,
            $this->country,
        ]));
    }
}
