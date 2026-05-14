<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('futures_trading_orders', function (Blueprint $table) {
            $table->boolean('is_copy')->default(false)->after('order_id');
            $table->unsignedBigInteger('copied_from_user_id')->nullable()->after('is_copy');
            $table->unsignedBigInteger('copied_from_order_id')->nullable()->after('copied_from_user_id');
            $table->unsignedBigInteger('copy_relationship_id')->nullable()->after('copied_from_order_id');
            $table->index(['is_copy', 'copied_from_user_id']);
        });

        Schema::table('margin_trading_orders', function (Blueprint $table) {
            $table->boolean('is_copy')->default(false)->after('order_mode');
            $table->unsignedBigInteger('copied_from_user_id')->nullable()->after('is_copy');
            $table->unsignedBigInteger('copied_from_order_id')->nullable()->after('copied_from_user_id');
            $table->unsignedBigInteger('copy_relationship_id')->nullable()->after('copied_from_order_id');
            $table->index(['is_copy', 'copied_from_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('futures_trading_orders', function (Blueprint $table) {
            $table->dropIndex(['is_copy', 'copied_from_user_id']);
            $table->dropColumn(['is_copy', 'copied_from_user_id', 'copied_from_order_id', 'copy_relationship_id']);
        });

        Schema::table('margin_trading_orders', function (Blueprint $table) {
            $table->dropIndex(['is_copy', 'copied_from_user_id']);
            $table->dropColumn(['is_copy', 'copied_from_user_id', 'copied_from_order_id', 'copy_relationship_id']);
        });
    }
};

