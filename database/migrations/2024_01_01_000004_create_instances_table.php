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
        Schema::create('instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('resource_package_id')->constrained();
            $table->foreignId('price_plan_id')->constrained();
            $table->string('name', 50);
            $table->enum('status', [
                'pending', 'creating', 'running', 'stopped',
                'failed', 'cancelled', 'expired', 'suspended',
                'error', 'terminated'
            ])->default('pending');
            $table->enum('billing_status', [
                'not_started', 'billing_active', 'billing_stopped',
                'period_ended', 'payment_insufficient', 'closed'
            ])->default('not_started');
            $table->foreignId('image_id')->nullable()->constrained('instance_images');
            $table->text('ssh_public_key')->nullable();
            $table->string('ssh_password')->nullable();
            $table->string('ssh_host', 100)->nullable();
            $table->unsignedInteger('ssh_port')->nullable();
            $table->string('ssh_username', 50)->nullable();
            $table->string('jupyterlab_url', 200)->nullable();
            $table->string('local_storage_path', 200)->nullable();
            $table->timestamp('billing_started_at')->nullable();
            $table->timestamp('period_started_at')->nullable();
            $table->timestamp('period_ended_at')->nullable();
            $table->decimal('total_usage_hours', 10, 2)->default(0);
            $table->decimal('total_cost', 12, 4)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instances');
    }
};