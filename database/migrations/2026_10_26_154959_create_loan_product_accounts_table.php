<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_product_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_product_id');
            $table->foreign('loan_product_id')->references('id')->on('loan_products')->onDelete('cascade');
            $table->string('operation_type');
            $table->uuid('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['loan_product_id', 'operation_type']);
            $table->index(['loan_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_product_accounts');
    }
};
