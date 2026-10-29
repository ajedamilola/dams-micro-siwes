<?php

use App\Enums\GeneralLedger\AccountCategory;
use App\Models\GeneralLedger\Ledger;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->string('description');
            $table->string('parent_id')->nullable();
            $table->foreignIdFor(Ledger::class)->constrained();
            $table->string('subledger_id')->nullable();
            $table->enum('balance_type', ['credit', 'debit']);
            $table->decimal('display_balance', 20, 4)->default(0);
            $table->string('category')->default(AccountCategory::Uncategorized);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
