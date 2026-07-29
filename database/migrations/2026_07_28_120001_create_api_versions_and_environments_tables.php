<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->text('description')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('api_environments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('base_url');
            $table->string('badge')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('api_version_environment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_environment_id')->constrained()->cascadeOnDelete();
            $table->string('base_url_override')->nullable();
            $table->timestamps();

            $table->unique(['api_version_id', 'api_environment_id'], 'version_environment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_version_environment');
        Schema::dropIfExists('api_environments');
        Schema::dropIfExists('api_versions');
    }
};
