<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Map Laravel password verification to our password_hash column
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Relationship to retrieve customer's orders dynamically.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_email', 'email');
    }
}
