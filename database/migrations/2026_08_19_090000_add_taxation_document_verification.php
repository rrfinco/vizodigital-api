<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxation_orders', function (Blueprint $table): void {
            $table->string('documents_status')->default('pending')->after('status');
            $table->text('documents_note')->nullable()->after('documents_status');
            $table->timestamp('documents_reviewed_at')->nullable()->after('documents_note');
            $table->foreignId('documents_reviewed_by')->nullable()->after('documents_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('taxation_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('taxation_order_id')->constrained('taxation_orders')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status')->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['taxation_order_id', 'status'], 'tax_doc_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxation_documents');

        Schema::table('taxation_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('documents_reviewed_by');
            $table->dropColumn([
                'documents_status',
                'documents_note',
                'documents_reviewed_at',
            ]);
        });
    }
};
