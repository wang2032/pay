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
        Schema::create('price_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_package_id')->constrained()->onDelete('cascade');
            $table->enum('plan_type', ['hourly', 'daily', 'weekly', 'monthly']);
            $table->decimal('price', 12, 4);
            $table->string('currency', 10)->default('USDT');
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_at')->nullable();
            $table->timestamps();

            $table->index(['resource_package_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_plans');
    }
};