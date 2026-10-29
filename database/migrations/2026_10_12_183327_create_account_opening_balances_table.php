<?php

use App\Models\GeneralLedger\Account;
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
        Schema::create('account_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->string('date');
            $table->foreignIdFor(Account::class)->constrained()->onDelete('cascade');
            $table->decimal('balance', 20, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_opening_balances');
    }
};
