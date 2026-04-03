<?php

namespace Addons\CatminSlider\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $type  fullwidth|carousel|grid
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property array<string, mixed>|null $settings
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Collection<int, SliderItem> $items
 * @property-read Collection<int, SliderItem> $activeItems
 */
class Slider extends Model
{
    protected $table = 'sliders';

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'is_active',
        'starts_at',
        'ends_at',
        'settings',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'settings' => 'array',
    ];

    public const TYPE_FULLWIDTH = 'fullwidth';
    public const TYPE_CAROUSEL = 'carousel';
    public const TYPE_GRID = 'grid';

    public const TYPES = [
        self::TYPE_FULLWIDTH => 'Pleine largeur (hero slider)',
        self::TYPE_CAROUSEL => 'Carrousel continu (logos / photos)',
        self::TYPE_GRID => 'Grille multi-colonnes',
    ];

    /** @return HasMany<SliderItem> */
    public function items(): HasMany
    {
        return $this->hasMany(SliderItem::class, 'slider_id')->orderBy('position');
    }

    /** @return HasMany<SliderItem> */
    public function activeItems(): HasMany
    {
        return $this->hasMany(SliderItem::class, 'slider_id')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('position');
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

    /** @return array<string, mixed> */
    public function defaultSettings(): array
    {
        return match ($this->type) {
            self::TYPE_FULLWIDTH => [
                'height' => '500px',
                'autoplay' => true,
                'interval' => 5000,
                'show_controls' => true,
                'show_indicators' => true,
            ],
            self::TYPE_CAROUSEL => [
                'height' => '120px',
                'scroll_speed' => 3000,
                'gap' => '24px',
            ],
            self::TYPE_GRID => [
                'height' => '300px',
                'columns' => 4,
            ],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    public function mergedSettings(): array
    {
        return array_merge($this->defaultSettings(), (array) ($this->settings ?? []));
    }
}
