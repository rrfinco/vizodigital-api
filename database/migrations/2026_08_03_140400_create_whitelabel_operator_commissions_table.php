<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelabel_operator_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whitelabel_id')
                ->constrained('whitelabels')
                ->cascadeOnDelete();
            $table->string('operator_type'); // mobile / dth
            $table->unsignedInteger('operator_sp_key');
            $table->decimal('commission_percentage', 6, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                ['whitelabel_id', 'operator_type', 'operator_sp_key'],
                'wlopc_wl_type_sp_unique'
            );
            $table->index(['operator_type', 'operator_sp_key'], 'wlopc_type_sp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_operator_commissions');
    }
};
