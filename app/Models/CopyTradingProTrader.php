<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CopyTradingProTrader extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'style',
        'risk_level',
        'profit_share_percent',
        'min_investment_amount',
        'min_investment_currency',
        'status',
    ];

    protected $casts = [
        'profit_share_percent' => 'float',
        'min_investment_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relationships()
    {
        return $this->hasMany(CopyTradingRelationship::class, 'pro_trader_id');
    }
}

