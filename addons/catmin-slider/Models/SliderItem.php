<?php

namespace Addons\CatminSlider\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $slider_id
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $content
 * @property string|null $cta_label
 * @property string|null $cta_url
 * @property int|null $media_id
 * @property string|null $media_url
 * @property string|null $link_type  page|article|event|product|url
 * @property int|null $link_id
 * @property int $position
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property array<string, mixed>|null $payload
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Slider $slider
 */
class SliderItem extends Model
{
    protected $table = 'slider_items';

    /** @var array<int, string> */
    protected $fillable = [
        'slider_id',
        'title',
        'subtitle',
        'content',
        'cta_label',
        'cta_url',
        'media_id',
        'media_url',
        'link_type',
        'link_id',
        'position',
        'is_active',
        'starts_at',
        'ends_at',
        'payload',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'media_id' => 'integer',
        'link_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'payload' => 'array',
    ];

    public const LINK_TYPES = [
        'page' => 'Page',
        'article' => 'Article',
        'event' => 'Événement',
        'product' => 'Produit',
        'url' => 'URL externe',
    ];

    /** @return BelongsTo<Slider, SliderItem> */
    public function slider(): BelongsTo
    {
        return $this->belongsTo(Slider::class, 'slider_id');
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function resolvedImageUrl(): ?string
    {
        if ($this->media_url !== null && $this->media_url !== '') {
            return $this->media_url;
        }

        return null;
    }
}
