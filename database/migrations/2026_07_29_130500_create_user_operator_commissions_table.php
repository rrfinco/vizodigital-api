<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pehle migration fail hone ke baad table create ho chuka ho sakta hai,
        // isliye create vs alter ko conditionally handle karte hain.
        if (! Schema::hasTable('user_operator_commissions')) {
            Schema::create('user_operator_commissions', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('operator_type'); // mobile / dth
                $table->unsignedInteger('operator_sp_key');

                // percentage e.g. 2.50
                $table->decimal('commission_percentage', 6, 2)->default(0);

                // whether this operator is usable for the user
                $table->boolean('status')->default(true);

                $table->timestamps();

                // MySQL constraint/index name length limit hoti hai, isliye short explicit names.
                $table->unique(['user_id', 'operator_type', 'operator_sp_key'], 'uopc_user_type_sp_unique');
                $table->index(['operator_type', 'operator_sp_key'], 'uopc_type_sp_idx');
            });

            return;
        }

        // Agar table already exists hai (jaise tumhare case me), toh missing unique/index add karo.
        Schema::table('user_operator_commissions', function (Blueprint $table): void {
            $table->unique(['user_id', 'operator_type', 'operator_sp_key'], 'uopc_user_type_sp_unique');
            $table->index(['operator_type', 'operator_sp_key'], 'uopc_type_sp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_operator_commissions');
    }
};

