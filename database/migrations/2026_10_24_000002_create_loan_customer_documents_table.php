<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_customer_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_customer_id')->constrained('loan_customers')->onDelete('cascade');
            $table->string('doc_type');
            $table->string('doc_number')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_customer_id', 'doc_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_customer_documents');
    }
};
