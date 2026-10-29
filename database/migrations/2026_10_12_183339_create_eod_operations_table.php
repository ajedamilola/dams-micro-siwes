<?php

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
        Schema::create('eod_operations', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['STARTED', 'RUNNING', 'FAILED', 'COMPLETED']);
            $table->date('date');
            $table->string('failure_reason')->nullable();
            $table->json('logs')->default('[]');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eod_operations');
    }
};
