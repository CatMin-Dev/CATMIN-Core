<?php

namespace Addons\CatminEventShopBridge\Models;

use Illuminate\Database\Eloquent\Model;
use Addons\CatEvent\Models\Event;
use Addons\CatminShop\Models\Product;

class EventShopTicketType extends Model
{
    protected $table = 'event_shop_bridge_ticket_types';

    protected $fillable = [
        'event_id',
        'shop_product_id',
        'name',
        'slug',
        'sku',
        'price',
        'allocation',
        'auto_cancel_on_order_cancel',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'allocation' => 'integer',
            'auto_cancel_on_order_cancel' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'shop_product_id');
    }

    public function orderLinks()
    {
        return $this->hasMany(EventShopOrderLink::class, 'ticket_type_id');
    }
}