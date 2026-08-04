<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whitelabel_domains', function (Blueprint $table): void {
            $table->string('role', 32)->default('portal')->after('host');
            $table->index(['whitelabel_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('whitelabel_domains', function (Blueprint $table): void {
            $table->dropIndex(['whitelabel_id', 'role']);
            $table->dropColumn('role');
        });
    }
};
