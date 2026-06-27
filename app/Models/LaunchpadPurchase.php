<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaunchpadPurchase extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'quote_currency',
        'quote_amount',
        'token_amount',
        'price',
        'status',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'quote_amount' => 'decimal:8',
            'token_amount' => 'decimal:8',
            'price' => 'decimal:8',
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

