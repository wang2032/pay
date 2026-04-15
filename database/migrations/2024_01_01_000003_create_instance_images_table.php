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
        Schema::create('instance_images', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('version', 20);
            $table->string('base_system', 50);
            $table->string('cuda_version', 20);
            $table->string('framework', 100);
            $table->timestamp('updated_at');
            $table->enum('compatibility_status', ['compatible', 'warning', 'incompatible'])->default('compatible');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instance_images');
    }
};