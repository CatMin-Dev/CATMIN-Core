<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'shop_invoices';

    protected $fillable = [
        'invoice_number',
        'order_id',
        'customer_id',
        'status',
        'currency',
        'total',
        'issued_on',
        'due_on',
        'html_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'issued_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
