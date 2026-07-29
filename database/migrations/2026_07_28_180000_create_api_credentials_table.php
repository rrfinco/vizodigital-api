<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_environment_id')->constrained('api_environments')->cascadeOnDelete();
            $table->string('client_id');
            $table->text('api_secret');
            $table->string('merchant_id')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'api_environment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_credentials');
    }
};
