<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_environment_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['api_endpoint_id', 'api_environment_id']);
        });

        Schema::create('code_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_environment_id')->constrained()->cascadeOnDelete();
            $table->string('language');
            $table->longText('code');
            $table->boolean('is_generated')->default(false);
            $table->boolean('is_override')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['api_endpoint_id', 'api_environment_id', 'language'], 'code_samples_unique');
        });

        Schema::create('endpoint_base_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_environment_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('urlable'); // endpoint|group|category|version
            $table->string('base_url');
            $table->timestamps();

            $table->unique(['api_environment_id', 'urlable_type', 'urlable_id'], 'endpoint_base_urls_unique');
        });

        Schema::create('postman_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_environment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('draft');
            $table->string('file_path')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['api_version_id', 'api_environment_id', 'slug'], 'postman_collections_unique');
        });

        Schema::create('sdk_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('language');
            $table->string('status')->default('draft');
            $table->longText('install_md')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('package_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['slug', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdk_packages');
        Schema::dropIfExists('postman_collections');
        Schema::dropIfExists('endpoint_base_urls');
        Schema::dropIfExists('code_samples');
        Schema::dropIfExists('endpoint_examples');
    }
};
