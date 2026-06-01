<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroImage extends Model
{
    protected $table = 'hero_images';

    protected $fillable = [
        'filename',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
