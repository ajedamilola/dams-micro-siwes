<?php

use App\Models\GeneralLedger\Account;
use App\Models\GeneralLedger\JournalTransaction;
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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('value', 20, 4);
            $table->decimal('balance_after', 20, 4)->nullable();
            $table->foreignIdFor(JournalTransaction::class);
            $table->foreignIdFor(Account::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
