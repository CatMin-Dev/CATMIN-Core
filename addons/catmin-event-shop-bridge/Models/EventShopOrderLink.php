<?php

namespace Addons\CatminEventShopBridge\Models;

use Illuminate\Database\Eloquent\Model;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatminShop\Models\Order;
use Addons\CatminShop\Models\OrderItem;

class EventShopOrderLink extends Model
{
    protected $table = 'event_shop_bridge_order_links';

    protected $fillable = [
        'ticket_type_id',
        'shop_order_id',
        'shop_order_item_id',
        'event_id',
        'event_participant_id',
        'event_ticket_id',
        'unit_index',
        'source_key',
        'status',
        'customer_email',
        'customer_name',
        'integration_error',
        'issued_at',
        'cancelled_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function ticketType()
    {
        return $this->belongsTo(EventShopTicketType::class, 'ticket_type_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'shop_order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'shop_order_item_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function participant()
    {
        return $this->belongsTo(EventParticipant::class, 'event_participant_id');
    }

    public function ticket()
    {
        return $this->belongsTo(EventTicket::class, 'event_ticket_id');
    }
}