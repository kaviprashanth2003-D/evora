<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'product_code',
        'name',
        'category',
        'description',
        'image1',
        'image2',
        'image3',
        'image4',
        'original_price',
        'discount_price',
        'discount_active',
        'offer_badge',
        'stock_xs',
        'stock_s',
        'stock_m',
        'stock_l',
        'stock_xl',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_active' => 'boolean',
        'stock_xs' => 'integer',
        'stock_s' => 'integer',
        'stock_m' => 'integer',
        'stock_l' => 'integer',
        'stock_xl' => 'integer',
    ];
}
