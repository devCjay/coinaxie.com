<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadProject extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'token_symbol',
        'token_name',
        'token_decimals',
        'token_logo_url',
        'description',
        'quote_currency',
        'sale_price',
        'hard_cap_quote',
        'min_buy_quote',
        'max_buy_quote',
        'sold_quote',
        'sold_tokens',
        'sale_start_at',
        'sale_end_at',
        'launch_at',
        'status',
        'trading_enabled',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:8',
            'hard_cap_quote' => 'decimal:8',
            'min_buy_quote' => 'decimal:8',
            'max_buy_quote' => 'decimal:8',
            'sold_quote' => 'decimal:8',
            'sold_tokens' => 'decimal:8',
            'sale_start_at' => 'datetime',
            'sale_end_at' => 'datetime',
            'launch_at' => 'datetime',
            'trading_enabled' => 'boolean',
        ];
    }

    public function purchases()
    {
        return $this->hasMany(LaunchpadPurchase::class, 'project_id');
    }

    public function market()
    {
        return $this->hasOne(LaunchpadMarket::class, 'project_id');
    }
}

