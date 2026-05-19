<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CopyTradingRelationship extends Model
{
    protected $fillable = [
        'pro_trader_id',
        'follower_id',
        'market_type',
        'allocation_type',
        'allocation_value',
        'stop_loss_percent',
        'max_leverage',
        'margin_order_mode',
        'status',
    ];

    protected $casts = [
        'allocation_value' => 'float',
        'stop_loss_percent' => 'float',
    ];

    public function proTrader()
    {
        return $this->belongsTo(CopyTradingProTrader::class, 'pro_trader_id');
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }
}

