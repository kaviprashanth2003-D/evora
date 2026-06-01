<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_hash',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'city',
        'zip',
        'delivery_tier',
        'shipping_fee',
        'payment_method',
        'receipt_path',
        'subtotal',
        'discount_amount',
        'total',
        'status',
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relationship to Order Items.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
