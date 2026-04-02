<?php

namespace Addons\CatminShop\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'shop_customers';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'notes',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id')->latest();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id')->latest();
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }
}
