<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launchpad_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 32)->default('nowpayments');
            $table->string('reference', 64)->unique();
            $table->string('status', 32)->default('pending');

            $table->decimal('quote_amount', 24, 8)->default(0);
            $table->string('quote_currency', 16)->default('USDT');

            $table->string('pay_currency', 32)->nullable();
            $table->decimal('pay_amount', 24, 8)->nullable();
            $table->string('pay_address', 191)->nullable();
            $table->string('payment_id', 64)->nullable();
            $table->string('payment_status', 64)->nullable();
            $table->string('transaction_hash', 191)->nullable();
            $table->unsignedBigInteger('expires_at')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launchpad_payment_intents');
    }
};

