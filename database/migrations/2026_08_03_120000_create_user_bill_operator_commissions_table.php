<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bill_operator_commissions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('opcode', 32);
            // percentage | flat
            $table->string('commission_type', 16)->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['user_id', 'opcode'], 'uboc_user_opcode_unique');
            $table->index('opcode', 'uboc_opcode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bill_operator_commissions');
    }
};
