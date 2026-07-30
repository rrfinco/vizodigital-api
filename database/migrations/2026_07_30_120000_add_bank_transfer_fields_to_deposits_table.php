<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('method')->default('online')->after('amount')->index();
            $table->string('utr')->nullable()->after('gateway_ref');
            $table->string('proof_path')->nullable()->after('utr');
            $table->text('admin_notes')->nullable()->after('proof_path');
            $table->foreignId('reviewed_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'method',
                'utr',
                'proof_path',
                'admin_notes',
                'reviewed_at',
            ]);
        });
    }
};
