<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'shop_products';

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'compare_at_price',
        'description',
        'stock_quantity',
        'low_stock_threshold',
        'status',
        'visibility',
        'manage_stock',
        'image_path',
        'product_type',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'manage_stock' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'shop_category_product', 'product_id', 'category_id')->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function isLowStock(): bool
    {
        return (bool) $this->manage_stock && (int) $this->stock_quantity <= (int) $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return (bool) $this->manage_stock && (int) $this->stock_quantity <= 0;
    }
}
