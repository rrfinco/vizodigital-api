<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('credit_card_pay'); // credit_card_fetch, credit_card_pay
            $table->string('order_id')->index();
            $table->string('mobile', 20);
            $table->string('card', 32);
            $table->string('opcode', 32);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('fetch_id')->nullable();
            $table->string('pan')->nullable();
            $table->string('status')->default('pending')->index(); // pending, success, failed
            $table->string('provider_txid')->nullable();
            $table->string('utr')->nullable();
            $table->string('error_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payment_transactions');
    }
};
