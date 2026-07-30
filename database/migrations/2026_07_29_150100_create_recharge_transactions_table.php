<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_request_id')->nullable();
            $table->string('api_request_id')->unique();
            $table->unsignedInteger('operator_sp_key');
            $table->string('operator_type'); // mobile, dth
            $table->string('account_number');
            $table->decimal('amount', 10, 2);
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2);
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('rpid')->nullable(); // Roundpay ID
            $table->string('opid')->nullable(); // Operator Ref ID
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['api_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_transactions');
    }
};
