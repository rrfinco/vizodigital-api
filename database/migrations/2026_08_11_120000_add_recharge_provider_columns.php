<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('recharge_provider', 32)
                ->default('roundpay')
                ->after('whitelabel_id');
        });

        Schema::table('whitelabels', function (Blueprint $table): void {
            $table->string('recharge_provider', 32)
                ->default('roundpay')
                ->after('status');
        });

        Schema::table('recharge_transactions', function (Blueprint $table): void {
            $table->string('provider', 32)
                ->default('roundpay')
                ->after('user_id');
            $table->string('circle')->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('recharge_provider');
        });

        Schema::table('whitelabels', function (Blueprint $table): void {
            $table->dropColumn('recharge_provider');
        });

        Schema::table('recharge_transactions', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'circle']);
        });
    }
};
