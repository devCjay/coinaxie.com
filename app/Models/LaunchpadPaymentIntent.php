<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadPaymentIntent extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'provider',
        'reference',
        'status',
        'quote_amount',
        'quote_currency',
        'pay_currency',
        'pay_amount',
        'pay_address',
        'payment_id',
        'payment_status',
        'transaction_hash',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quote_amount' => 'decimal:8',
            'pay_amount' => 'decimal:8',
            'expires_at' => 'integer',
        ];
    }

    public function project()
    {
        return $this->belongsTo(LaunchpadProject::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

