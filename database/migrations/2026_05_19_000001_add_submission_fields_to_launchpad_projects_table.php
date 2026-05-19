<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('launchpad_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('id');
            $table->enum('approval_status', ['approved', 'pending', 'rejected'])->default('approved')->after('status');
            $table->boolean('is_visible')->default(true)->after('approval_status');

            $table->string('launch_fee_currency', 16)->nullable()->after('is_visible');
            $table->decimal('launch_fee_amount', 28, 8)->default(0)->after('launch_fee_currency');
            $table->timestamp('launch_fee_paid_at')->nullable()->after('launch_fee_amount');
            $table->timestamp('admin_approved_at')->nullable()->after('launch_fee_paid_at');
            $table->unsignedBigInteger('admin_approved_by')->nullable()->after('admin_approved_at');

            $table->index(['approval_status', 'is_visible'], 'lp_prj_appr_vis');
            $table->index(['created_by_user_id', 'created_at'], 'lp_prj_creator_dt');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_projects', function (Blueprint $table) {
            $table->dropIndex('lp_prj_appr_vis');
            $table->dropIndex('lp_prj_creator_dt');

            $table->dropColumn([
                'created_by_user_id',
                'approval_status',
                'is_visible',
                'launch_fee_currency',
                'launch_fee_amount',
                'launch_fee_paid_at',
                'admin_approved_at',
                'admin_approved_by',
            ]);
        });
    }
};
