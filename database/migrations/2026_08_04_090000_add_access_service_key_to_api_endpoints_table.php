<?php

use App\Services\PlanApi\PlanApiService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_endpoints', function (Blueprint $table): void {
            $table->string('access_service_key')->nullable()->after('permission_name')->index();
        });

        $slugToService = [
            'operator-fetch' => PlanApiService::SERVICE_OPERATOR_FETCH,
            'operator-plan-fetch' => PlanApiService::SERVICE_OPERATOR_PLAN_FETCH,
            'dth-plan-fetch' => PlanApiService::SERVICE_DTH_PLAN_FETCH,
            'dth-info' => PlanApiService::SERVICE_DTH_INFO,
        ];

        foreach ($slugToService as $slug => $service) {
            DB::table('api_endpoints')
                ->where('slug', $slug)
                ->update(['access_service_key' => $service]);
        }
    }

    public function down(): void
    {
        Schema::table('api_endpoints', function (Blueprint $table): void {
            $table->dropColumn('access_service_key');
        });
    }
};
