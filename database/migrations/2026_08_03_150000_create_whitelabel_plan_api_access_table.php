<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelabel_plan_api_access', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('whitelabel_id')
                ->constrained('whitelabels')
                ->cascadeOnDelete();

            // operator_fetch | operator_plan_fetch | dth_plan_fetch | dth_info
            $table->string('service', 64);

            $table->boolean('status')->default(false);

            $table->decimal('per_call_fee', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['whitelabel_id', 'service'], 'wlpaa_wl_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelabel_plan_api_access');
    }
};
