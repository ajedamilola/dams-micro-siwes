<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Company::class)->constrained()->onDelete('cascade');
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('interest_method')->default('flat');
            $table->decimal('annual_interest_rate', 8, 4)->default(0);
            $table->string('repayment_frequency')->default('monthly');
            $table->decimal('min_principal', 20, 2)->default(0);
            $table->decimal('max_principal', 20, 2)->default(999999999999.99);
            $table->unsignedInteger('min_tenor')->default(1);
            $table->unsignedInteger('max_tenor')->default(60);
            $table->decimal('processing_fee_rate', 8, 4)->default(0);
            $table->decimal('processing_fee_flat', 20, 2)->default(0);
            $table->decimal('penalty_rate_daily', 8, 4)->default(0);
            $table->unsignedInteger('penalty_grace_days')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
