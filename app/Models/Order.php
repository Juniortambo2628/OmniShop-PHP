<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id', 'event_slug', 'company_name', 'contact_name', 'email', 'phone',
        'booth_number', 'subtotal', 'vat', 'total', 'status', 'notes',
        'promo_code', 'discount_amount', 'delivery_cost'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }
}
