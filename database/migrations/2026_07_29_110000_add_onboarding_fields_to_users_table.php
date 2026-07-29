<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('email');
            $table->string('phone', 50)->nullable()->after('company_name');
            $table->string('onboarding_status')->default('approved')->after('phone');
            $table->string('kyc_token', 64)->nullable()->unique()->after('onboarding_status');
            $table->timestamp('kyc_token_expires_at')->nullable()->after('kyc_token');
            $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_token_expires_at');
            $table->timestamp('approved_at')->nullable()->after('kyc_submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'company_name',
                'phone',
                'onboarding_status',
                'kyc_token',
                'kyc_token_expires_at',
                'kyc_submitted_at',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
