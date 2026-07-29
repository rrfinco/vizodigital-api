<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('show_in_sidebar')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['api_version_id', 'slug']);
            $table->index(['api_version_id', 'status', 'sort_order']);
        });

        Schema::create('api_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['api_category_id', 'slug']);
            $table->index(['api_category_id', 'status', 'sort_order']);
        });

        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_version_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('method', 16)->index();
            $table->string('path');
            $table->string('summary')->nullable();
            $table->longText('description_md')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('permission_name')->nullable();
            $table->string('rate_limit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['api_version_id', 'slug']);
            $table->index(['api_group_id', 'status', 'sort_order']);
            $table->index(['method', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
        Schema::dropIfExists('api_groups');
        Schema::dropIfExists('api_categories');
    }
};
