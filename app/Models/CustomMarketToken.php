<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomMarketToken extends Model
{
    protected $fillable = [
        'ticker',
        'market',
        'current_price',
        'open_price',
        'high',
        'low',
        'volume',
        'change_1d_percentage',
        'is_active',
    ];

    protected $casts = [
        'current_price' => 'float',
        'open_price' => 'float',
        'high' => 'float',
        'low' => 'float',
        'volume' => 'float',
        'change_1d_percentage' => 'float',
        'is_active' => 'boolean',
    ];
}

