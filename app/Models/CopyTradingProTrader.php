<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CopyTradingProTrader extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'status',
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

