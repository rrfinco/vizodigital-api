<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentation_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('documentation_pages')->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->string('slug');
            $table->longText('body_md')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('sidebar_key')->nullable()->index();
            $table->boolean('show_in_sidebar')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['api_version_id', 'slug']);
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documentation_page_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->string('title')->nullable();
            $table->longText('body_md')->nullable();
            $table->json('config')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question');
            $table->longText('answer_md');
            $table->string('category')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('changelog_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body_md');
            $table->string('status')->default('draft')->index();
            $table->timestamp('released_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['api_version_id', 'slug']);
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('mediable');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('alt')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['api_version_id', 'is_visible', 'sort_order']);
        });

        Schema::create('search_index', function (Blueprint $table) {
            $table->id();
            $table->morphs('searchable');
            $table->foreignId('api_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->text('keywords')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->unique(['searchable_type', 'searchable_id']);
            $table->index(['status', 'type']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('search_index');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('changelog_entries');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('documentation_pages');
    }
};
