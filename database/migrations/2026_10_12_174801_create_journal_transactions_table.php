<?php

use App\Enums\GeneralLedger\TransactionStatus;
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
        Schema::create('journal_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description')->default('');
            $table->string('ref');
            $table->enum('status', TransactionStatus::caseValues())->default(TransactionStatus::DRAFT->value);
            $table->string('old_reversed_transaction_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('effective_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_transactions');
    }
};
