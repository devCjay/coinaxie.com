<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('launchpad_projects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('token_symbol', 32);
            $table->string('token_name')->nullable();
            $table->unsignedTinyInteger('token_decimals')->default(8);
            $table->string('token_logo_url')->nullable();
            $table->text('description')->nullable();
            $table->string('quote_currency', 16)->default('USDT');
            $table->decimal('sale_price', 28, 8);
            $table->decimal('hard_cap_quote', 28, 8)->default(0);
            $table->decimal('min_buy_quote', 28, 8)->default(0);
            $table->decimal('max_buy_quote', 28, 8)->default(0);
            $table->decimal('sold_quote', 28, 8)->default(0);
            $table->decimal('sold_tokens', 28, 8)->default(0);
            $table->dateTime('sale_start_at')->nullable();
            $table->dateTime('sale_end_at')->nullable();
            $table->dateTime('launch_at')->nullable();
            $table->enum('status', ['draft', 'live', 'ended', 'launched', 'canceled'])->default('draft');
            $table->boolean('trading_enabled')->default(false);
            $table->timestamps();
            $table->index(['status', 'sale_start_at', 'sale_end_at'], 'lp_proj_st_dt');
            $table->index(['token_symbol'], 'lp_proj_sym');
        });

        Schema::create('launchpad_purchases', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->string('quote_currency', 16)->default('USDT');
            $table->decimal('quote_amount', 28, 8);
            $table->decimal('token_amount', 28, 8);
            $table->decimal('price', 28, 8);
            $table->enum('status', ['reserved', 'refunded', 'allocated'])->default('reserved');
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'user_id'], 'lp_pur_pr_u');
            $table->index(['status'], 'lp_pur_st');
        });

        Schema::create('launchpad_markets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('symbol', 48)->unique();
            $table->string('base_currency', 32);
            $table->string('quote_currency', 16)->default('USDT');
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->decimal('last_price', 28, 8)->default(0);
            $table->decimal('volume_24h_base', 28, 8)->default(0);
            $table->decimal('volume_24h_quote', 28, 8)->default(0);
            $table->timestamps();
            $table->index(['status'], 'lp_mkt_st');
            $table->index(['project_id'], 'lp_mkt_pr');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launchpad_markets');
        Schema::dropIfExists('launchpad_purchases');
        Schema::dropIfExists('launchpad_projects');
    }
};

