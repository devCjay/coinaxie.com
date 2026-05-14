<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('copy_trading_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pro_trader_id')->constrained('copy_trading_pro_traders')->cascadeOnDelete();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->enum('market_type', ['futures', 'margin', 'both'])->default('both');
            $table->enum('allocation_type', ['fixed', 'percent'])->default('percent');
            $table->decimal('allocation_value', 28, 8)->default(0);
            $table->unsignedInteger('max_leverage')->default(50);
            $table->enum('margin_order_mode', ['normal', 'borrow'])->default('normal');
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();

            $table->unique(['pro_trader_id', 'follower_id']);
            $table->index(['pro_trader_id', 'market_type', 'status']);
            $table->index(['follower_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_trading_relationships');
    }
};

