<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadTrade extends Model
{
    protected $fillable = [
        'market_id',
        'maker_order_id',
        'taker_order_id',
        'maker_user_id',
        'taker_user_id',
        'price',
        'base_qty',
        'quote_qty',
        'taker_side',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'base_qty' => 'decimal:8',
            'quote_qty' => 'decimal:8',
            'timestamp' => 'integer',
        ];
    }

    public function market()
    {
        return $this->belongsTo(LaunchpadMarket::class, 'market_id');
    }
}

