<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelabel_bill_operator_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whitelabel_id')
                ->constrained('whitelabels')
                ->cascadeOnDelete();
            $table->string('opcode', 32);
            $table->string('commission_type', 16)->default('percentage'); // percentage | flat
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['whitelabel_id', 'opcode'], 'wlboc_wl_opcode_unique');
            $table->index('opcode', 'wlboc_opcode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_bill_operator_commissions');
    }
};
