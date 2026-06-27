<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('launchpad_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['limit', 'market']);
            $table->decimal('price', 28, 8)->nullable();
            $table->decimal('base_qty', 28, 8);
            $table->decimal('filled_base_qty', 28, 8)->default(0);
            $table->decimal('locked_quote', 28, 8)->default(0);
            $table->decimal('locked_base', 28, 8)->default(0);
            $table->enum('status', ['pending', 'partially_filled', 'filled', 'canceled'])->default('pending');
            $table->bigInteger('timestamp')->nullable();
            $table->timestamps();

            $table->index(['market_id', 'status', 'side'], 'lp_ord_mkt_st');
            $table->index(['user_id', 'created_at'], 'lp_ord_usr_dt');
        });

        Schema::create('launchpad_trades', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('maker_order_id');
            $table->unsignedBigInteger('taker_order_id');
            $table->unsignedBigInteger('maker_user_id');
            $table->unsignedBigInteger('taker_user_id');
            $table->decimal('price', 28, 8);
            $table->decimal('base_qty', 28, 8);
            $table->decimal('quote_qty', 28, 8);
            $table->enum('taker_side', ['buy', 'sell']);
            $table->bigInteger('timestamp')->nullable();
            $table->timestamps();

            $table->index(['market_id', 'created_at'], 'lp_trd_mkt_dt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launchpad_trades');
        Schema::dropIfExists('launchpad_orders');
    }
};

