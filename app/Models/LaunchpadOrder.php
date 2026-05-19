<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadOrder extends Model
{
    protected $fillable = [
        'market_id',
        'user_id',
        'side',
        'type',
        'price',
        'base_qty',
        'filled_base_qty',
        'locked_quote',
        'locked_base',
        'status',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'base_qty' => 'decimal:8',
            'filled_base_qty' => 'decimal:8',
            'locked_quote' => 'decimal:8',
            'locked_base' => 'decimal:8',
            'timestamp' => 'integer',
        ];
    }

    public function market()
    {
        return $this->belongsTo(LaunchpadMarket::class, 'market_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

