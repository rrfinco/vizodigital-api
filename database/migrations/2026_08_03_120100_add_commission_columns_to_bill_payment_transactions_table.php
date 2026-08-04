<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_payment_transactions', function (Blueprint $table): void {
            $table->string('commission_type', 16)->nullable()->after('amount');
            $table->decimal('commission_value', 10, 2)->nullable()->after('commission_type');
            $table->decimal('commission_amount', 12, 2)->nullable()->after('commission_value');
        });
    }

    public function down(): void
    {
        Schema::table('bill_payment_transactions', function (Blueprint $table): void {
            $table->dropColumn(['commission_type', 'commission_value', 'commission_amount']);
        });
    }
};
