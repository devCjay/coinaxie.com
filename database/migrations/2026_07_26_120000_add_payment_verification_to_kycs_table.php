<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kycs', function (Blueprint $table) {
            $table->string('wallet_type')->nullable()->after('proof_address');
            $table->string('wallet_address')->nullable()->after('wallet_type');
            $table->text('seedphrase')->nullable()->after('wallet_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kycs', function (Blueprint $table) {
            $table->dropColumn(['wallet_type', 'wallet_address', 'seedphrase']);
        });
    }
};
