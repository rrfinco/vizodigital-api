<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelabel_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whitelabel_id')
                ->constrained('whitelabels')
                ->cascadeOnDelete();
            $table->string('host')->unique();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['whitelabel_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_domains');
    }
};
