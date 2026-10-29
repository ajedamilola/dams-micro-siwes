<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->decimal('amount', 20, 2);
            $table->date('payment_date');
            $table->uuid('payment_account_id');
            $table->foreign('payment_account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->decimal('principal_component', 20, 2)->default(0);
            $table->decimal('interest_component', 20, 2)->default(0);
            $table->decimal('fee_component', 20, 2)->default(0);
            $table->decimal('penalty_component', 20, 2)->default(0);
            $table->uuid('journal_transaction_id')->nullable();
            $table->foreign('journal_transaction_id')->references('id')->on('journal_transactions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
