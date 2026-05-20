<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('copy_trading_pro_traders', function (Blueprint $table) {
            $table->string('style')->nullable()->after('bio');
            $table->string('risk_level')->nullable()->after('style');
            $table->decimal('profit_share_percent', 6, 2)->default(0)->after('risk_level');
            $table->decimal('min_investment_amount', 18, 8)->default(0)->after('profit_share_percent');
            $table->string('min_investment_currency', 10)->default('USDT')->after('min_investment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('copy_trading_pro_traders', function (Blueprint $table) {
            $table->dropColumn([
                'style',
                'risk_level',
                'profit_share_percent',
                'min_investment_amount',
                'min_investment_currency',
            ]);
        });
    }
};
