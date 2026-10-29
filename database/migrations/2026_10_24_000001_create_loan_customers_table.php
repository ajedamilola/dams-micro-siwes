<?php

use App\Models\Company;
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
        Schema::create('loan_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_no')->unique();
            $table->foreignIdFor(Company::class)->constrained()->onDelete('cascade');
            $table->uuid('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();

            $table->string('customer_type')->default('individual');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('business_name')->nullable();
            $table->string('rc_number')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('lga')->nullable();
            $table->string('nationality')->default('Nigeria');

            // CBN KYC identifiers. BVN and NIN are each 11 digits. Tier1 needs
            // BVN or NIN, Tier2 and Tier3 need both (Dec 2023 CBN amendment).
            $table->string('bvn', 11)->nullable();
            $table->string('nin', 11)->nullable();
            $table->string('means_of_id_type')->nullable();
            $table->string('means_of_id_number')->nullable();
            $table->date('id_expiry_date')->nullable();
            $table->string('proof_of_address_type')->nullable();

            $table->string('kyc_tier')->default('tier3');
            $table->string('kyc_status')->default('pending');
            $table->string('risk_rating')->default('low');
            $table->boolean('is_pep')->default(false);

            $table->string('employment_status')->nullable();
            $table->string('employer_name')->nullable();
            $table->decimal('monthly_income', 20, 2)->nullable();

            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_relationship')->nullable();
            $table->string('next_of_kin_phone')->nullable();

            $table->string('photo_path')->nullable();
            $table->string('signature_path')->nullable();

            $table->string('status')->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'phone']);
            $table->unique(['company_id', 'bvn']);
            $table->unique(['company_id', 'nin']);
            $table->index(['company_id', 'kyc_status']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_customers');
    }
};
