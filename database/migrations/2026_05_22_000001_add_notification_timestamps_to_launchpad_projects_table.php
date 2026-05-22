<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('launchpad_projects', 'sale_finalized_notified_at')) {
            Schema::table('launchpad_projects', function (Blueprint $table) {
                $table->timestamp('sale_finalized_notified_at')->nullable()->after('admin_approved_at');
            });
        }

        if (!Schema::hasColumn('launchpad_projects', 'trading_enabled_notified_at')) {
            Schema::table('launchpad_projects', function (Blueprint $table) {
                $table->timestamp('trading_enabled_notified_at')->nullable()->after('sale_finalized_notified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('launchpad_projects', 'trading_enabled_notified_at')) {
            Schema::table('launchpad_projects', function (Blueprint $table) {
                $table->dropColumn('trading_enabled_notified_at');
            });
        }

        if (Schema::hasColumn('launchpad_projects', 'sale_finalized_notified_at')) {
            Schema::table('launchpad_projects', function (Blueprint $table) {
                $table->dropColumn('sale_finalized_notified_at');
            });
        }
    }
};
