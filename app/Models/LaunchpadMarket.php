<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadMarket extends Model
{
    protected $fillable = [
        'project_id',
        'symbol',
        'base_currency',
        'quote_currency',
        'status',
        'last_price',
        'volume_24h_base',
        'volume_24h_quote',
    ];

    protected function casts(): array
    {
        return [
            'last_price' => 'decimal:8',
            'volume_24h_base' => 'decimal:8',
            'volume_24h_quote' => 'decimal:8',
        ];
    }

    public function project()
    {
        return $this->belongsTo(LaunchpadProject::class, 'project_id');
    }

    public function orders()
    {
        return $this->hasMany(LaunchpadOrder::class, 'market_id');
    }

    public function trades()
    {
        return $this->hasMany(LaunchpadTrade::class, 'market_id');
    }
}

