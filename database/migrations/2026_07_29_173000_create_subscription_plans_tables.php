<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscription_plan_api_endpoint', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['subscription_plan_id', 'api_endpoint_id'], 'plan_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_api_endpoint');
        Schema::dropIfExists('subscription_plans');
    }
};
