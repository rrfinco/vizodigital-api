<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('component');
            $table->json('default_config')->nullable();
            $table->boolean('is_system')->default(true);
            $table->boolean('is_enabled_by_default')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('endpoint_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['api_endpoint_id', 'section_key']);
            $table->index(['api_endpoint_id', 'enabled', 'sort_order']);
        });

        Schema::create('endpoint_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('string');
            $table->boolean('required')->default(false);
            $table->text('description')->nullable();
            $table->string('example')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('endpoint_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('location'); // path|query|header|cookie
            $table->string('name');
            $table->string('type')->default('string');
            $table->boolean('required')->default(false);
            $table->text('description')->nullable();
            $table->string('example')->nullable();
            $table->json('schema')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['api_endpoint_id', 'location']);
        });

        Schema::create('endpoint_request_bodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('content_type')->default('application/json');
            $table->text('description')->nullable();
            $table->json('schema')->nullable();
            $table->json('example')->nullable();
            $table->boolean('required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('endpoint_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('status_code');
            $table->string('description')->nullable();
            $table->string('content_type')->default('application/json');
            $table->json('schema')->nullable();
            $table->json('example')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['api_endpoint_id', 'status_code']);
        });

        Schema::create('endpoint_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('error_code');
            $table->unsignedSmallInteger('status_code');
            $table->string('message')->nullable();
            $table->text('description')->nullable();
            $table->json('example')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('endpoint_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->longText('body_md');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('endpoint_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_endpoint_id')->constrained('api_endpoints')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['api_endpoint_id', 'related_endpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_relations');
        Schema::dropIfExists('endpoint_notes');
        Schema::dropIfExists('endpoint_errors');
        Schema::dropIfExists('endpoint_responses');
        Schema::dropIfExists('endpoint_request_bodies');
        Schema::dropIfExists('endpoint_parameters');
        Schema::dropIfExists('endpoint_headers');
        Schema::dropIfExists('endpoint_sections');
        Schema::dropIfExists('section_definitions');
    }
};
