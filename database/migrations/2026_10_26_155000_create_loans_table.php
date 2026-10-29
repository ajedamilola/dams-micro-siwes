<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('loan_no')->unique();
            $table->foreignIdFor(Company::class)->constrained()->onDelete('cascade');
            $table->uuid('loan_product_id');
            $table->foreign('loan_product_id')->references('id')->on('loan_products')->onDelete('restrict');
            $table->uuid('loan_customer_id');
            $table->foreign('loan_customer_id')->references('id')->on('loan_customers')->onDelete('cascade');
            $table->decimal('principal', 20, 2);
            $table->decimal('annual_interest_rate', 8, 4)->default(0);
            $table->string('interest_method')->default('flat');
            $table->string('repayment_frequency')->default('monthly');
            $table->unsignedInteger('tenor');
            $table->decimal('processing_fee', 20, 2)->default(0);
            $table->decimal('total_interest', 20, 2)->default(0);
            $table->decimal('total_repayable', 20, 2)->default(0);
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->date('first_due_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->uuid('disbursement_account_id')->nullable();
            $table->foreign('disbursement_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->uuid('disbursement_journal_id')->nullable();
            $table->foreign('disbursement_journal_id')->references('id')->on('journal_transactions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'loan_no']);
            $table->index(['company_id', 'status']);
            $table->index(['loan_customer_id']);
            $table->index(['loan_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
