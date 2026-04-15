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
        Schema::create('bill_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('instance_id')->nullable()->constrained();
            $table->enum('type', ['deduction', 'refill', 'refund', 'renewal']);
            $table->decimal('amount', 12, 4);
            $table->string('currency', 10)->default('USDT');
            $table->decimal('balance_before', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->string('description', 200)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_records');
    }
};