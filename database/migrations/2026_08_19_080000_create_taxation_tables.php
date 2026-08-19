<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxation_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('taxation_services', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->foreignId('taxation_category_id')
                ->constrained('taxation_categories')
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('default_commission_percentage', 6, 2)->default(2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['taxation_category_id', 'is_active'], 'tax_svc_cat_active_idx');
        });

        Schema::create('taxation_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whitelabel_id')->nullable()->constrained('whitelabels')->nullOnDelete();
            $table->string('client_request_id')->nullable();
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 10);
            $table->string('pan', 10);
            $table->string('aadhaar', 12);
            $table->text('residence_address');
            $table->string('residence_city');
            $table->string('residence_pincode', 6);
            $table->string('residence_state');
            $table->text('office_address');
            $table->string('office_city');
            $table->string('office_pincode', 6);
            $table->string('office_state');
            $table->timestamps();

            $table->unique(['user_id', 'client_request_id'], 'tax_client_user_req_unique');
            $table->index(['user_id', 'created_at'], 'tax_client_user_created_idx');
            $table->index('pan', 'tax_client_pan_idx');
            $table->index('phone', 'tax_client_phone_idx');
        });

        Schema::create('taxation_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whitelabel_id')->nullable()->constrained('whitelabels')->nullOnDelete();
            $table->foreignId('taxation_client_id')->constrained('taxation_clients')->restrictOnDelete();
            $table->unsignedInteger('taxation_service_id');
            $table->string('service_name');
            $table->decimal('amount', 10, 2);
            $table->decimal('commission_percentage', 6, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('whitelabel_commission_amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('client_request_id')->nullable();
            $table->string('api_request_id');
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->foreign('taxation_service_id')
                ->references('id')
                ->on('taxation_services')
                ->restrictOnDelete();

            $table->unique(['user_id', 'client_request_id'], 'tax_order_user_req_unique');
            $table->unique('api_request_id', 'tax_order_api_req_unique');
            $table->index(['user_id', 'status', 'created_at'], 'tax_order_user_status_idx');
            $table->index('whitelabel_id', 'tax_order_wl_idx');
        });

        Schema::create('user_taxation_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('taxation_service_id');
            $table->decimal('commission_percentage', 6, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('taxation_service_id')
                ->references('id')
                ->on('taxation_services')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'taxation_service_id'], 'utaxc_user_svc_unique');
        });

        Schema::create('whitelabel_taxation_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('whitelabel_id')->constrained('whitelabels')->cascadeOnDelete();
            $table->unsignedInteger('taxation_service_id');
            $table->decimal('commission_percentage', 6, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('taxation_service_id')
                ->references('id')
                ->on('taxation_services')
                ->cascadeOnDelete();

            $table->unique(['whitelabel_id', 'taxation_service_id'], 'wltaxc_wl_svc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_taxation_commissions');
        Schema::dropIfExists('user_taxation_commissions');
        Schema::dropIfExists('taxation_orders');
        Schema::dropIfExists('taxation_clients');
        Schema::dropIfExists('taxation_services');
        Schema::dropIfExists('taxation_categories');
    }
};
