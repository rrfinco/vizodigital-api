<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_status_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('lead_code', 64);
            $table->string('last_status', 32)->nullable();
            $table->timestamp('commissioned_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'lead_code'], 'lss_user_lead_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_snapshots');
    }
};
