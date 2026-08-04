<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the earliest row's client_request_id; rename later duplicates so the unique index can apply.
        $duplicates = DB::table('recharge_transactions')
            ->select('user_id', 'client_request_id')
            ->whereNotNull('client_request_id')
            ->where('client_request_id', '!=', '')
            ->groupBy('user_id', 'client_request_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $group) {
            $ids = DB::table('recharge_transactions')
                ->where('user_id', $group->user_id)
                ->where('client_request_id', $group->client_request_id)
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids->slice(1) as $id) {
                DB::table('recharge_transactions')
                    ->where('id', $id)
                    ->update([
                        'client_request_id' => $group->client_request_id.'-legacy-'.$id,
                    ]);
            }
        }

        Schema::table('recharge_transactions', function (Blueprint $table): void {
            // MySQL allows multiple NULLs in a unique composite index.
            $table->unique(['user_id', 'client_request_id'], 'recharge_user_client_request_unique');
        });
    }

    public function down(): void
    {
        Schema::table('recharge_transactions', function (Blueprint $table): void {
            $table->dropUnique('recharge_user_client_request_unique');
        });
    }
};
