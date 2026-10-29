<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_accruals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->uuid('loan_schedule_id')->nullable();
            $table->foreign('loan_schedule_id')->references('id')->on('loan_schedules')->nullOnDelete();
            $table->date('accrual_date');
            $table->decimal('interest_amount', 20, 2);
            $table->uuid('journal_transaction_id')->nullable();
            $table->foreign('journal_transaction_id')->references('id')->on('journal_transactions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['loan_schedule_id', 'accrual_date']);
            $table->index(['loan_id', 'accrual_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_accruals');
    }
};
