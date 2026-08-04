<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelabel_wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whitelabel_id')
                ->constrained('whitelabels')
                ->cascadeOnDelete();
            $table->decimal('amount', 15, 4); // positive credit, negative debit
            $table->string('type', 16); // debit | credit
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_before', 15, 4);
            $table->decimal('balance_after', 15, 4);
            $table->timestamps();

            $table->index('whitelabel_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_wallet_transactions');
    }
};
