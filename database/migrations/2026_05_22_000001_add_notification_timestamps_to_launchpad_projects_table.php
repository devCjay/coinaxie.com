<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_projects', function (Blueprint $table) {
            $table->timestamp('sale_finalized_notified_at')->nullable()->after('admin_approved_at');
            $table->timestamp('trading_enabled_notified_at')->nullable()->after('sale_finalized_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_projects', function (Blueprint $table) {
            $table->dropColumn(['sale_finalized_notified_at', 'trading_enabled_notified_at']);
        });
    }
};

