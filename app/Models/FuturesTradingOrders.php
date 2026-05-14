<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuturesTradingOrders extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'ticker',
        'side',
        'size',
        'price',
        'status',
        'order_id',
        'is_copy',
        'copied_from_user_id',
        'copied_from_order_id',
        'copy_relationship_id',
        'timestamp',
        'take_profit',
        'stop_loss',
        'locked_margin',
        'leverage'
    ];


    protected function casts(): array
    {
        return [
            'size' => 'float',
            'price' => 'float',
            'take_profit' => 'float',
            'stop_loss' => 'float',
            'locked_margin' => 'float',
            'leverage' => 'integer',
            'is_copy' => 'boolean',
            'copied_from_user_id' => 'integer',
            'copied_from_order_id' => 'integer',
            'copy_relationship_id' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
