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
        Schema::create('resource_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('gpu_model', 50);
            $table->unsignedTinyInteger('gpu_count');
            $table->unsignedInteger('vram'); // MB
            $table->unsignedTinyInteger('cpu_count');
            $table->string('cpu_model', 50);
            $table->unsignedInteger('memory'); // MB
            $table->string('region', 50);
            $table->string('driver_version', 20);
            $table->string('cuda_version', 20);
            $table->unsignedInteger('local_storage'); // GB
            $table->boolean('supports_ssh')->default(true);
            $table->boolean('supports_jupyterlab')->default(true);
            $table->boolean('is_active')->default(true);
            $table->enum('stock_status', ['available', 'scarce', 'sold_out'])->default('available');
            $table->timestamps();

            $table->index(['is_active', 'region']);
            $table->index(['gpu_model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_packages');
    }
};