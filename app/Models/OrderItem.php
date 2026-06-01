<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'size',
        'qty',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'qty' => 'integer',
    ];

    /**
     * Link back to the parent order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
