<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('whitelabel_id')
                ->nullable()
                ->after('id')
                ->constrained('whitelabels')
                ->nullOnDelete();

            $table->index('whitelabel_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('whitelabel_id');
        });
    }
};
