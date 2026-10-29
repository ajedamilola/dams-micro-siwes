<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->unsignedInteger('installment_no');
            $table->date('due_date');
            $table->decimal('principal_due', 20, 2)->default(0);
            $table->decimal('interest_due', 20, 2)->default(0);
            $table->decimal('fee_due', 20, 2)->default(0);
            $table->decimal('penalty_due', 20, 2)->default(0);
            $table->decimal('total_due', 20, 2)->default(0);
            $table->decimal('principal_paid', 20, 2)->default(0);
            $table->decimal('interest_paid', 20, 2)->default(0);
            $table->decimal('fee_paid', 20, 2)->default(0);
            $table->decimal('penalty_paid', 20, 2)->default(0);
            $table->decimal('accrued_interest', 20, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['loan_id', 'installment_no']);
            $table->index(['loan_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
