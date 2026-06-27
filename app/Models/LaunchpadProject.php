<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadProject extends Model
{
    protected $fillable = [
        'created_by_user_id',
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
        'approval_status',
        'is_visible',
        'trading_enabled',
        'launch_fee_currency',
        'launch_fee_amount',
        'launch_fee_paid_at',
        'admin_approved_at',
        'admin_approved_by',
        'sale_finalized_notified_at',
        'trading_enabled_notified_at',
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
            'is_visible' => 'boolean',
            'launch_fee_amount' => 'decimal:8',
            'launch_fee_paid_at' => 'datetime',
            'admin_approved_at' => 'datetime',
            'sale_finalized_notified_at' => 'datetime',
            'trading_enabled_notified_at' => 'datetime',
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
