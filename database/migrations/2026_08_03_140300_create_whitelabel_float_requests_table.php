<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelabel_float_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whitelabel_id')
                ->constrained('whitelabels')
                ->cascadeOnDelete();
            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->string('method', 32)->default('bank_transfer');
            $table->string('status', 32)->default('pending'); // pending | approved | rejected
            $table->string('utr')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['whitelabel_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_float_requests');
    }
};
