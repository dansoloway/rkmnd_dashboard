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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Tenant identifier (e.g., test_client)');
            $table->string('display_name')->comment('Human-readable name');
            $table->text('api_key')->comment('Encrypted API key for backend');
            $table->string('plan_type')->default('basic')->comment('Plan: basic, pro, enterprise');
            $table->boolean('is_active')->default(true)->comment('Whether tenant is active');
            $table->json('settings')->nullable()->comment('Additional tenant settings');
            $table->timestamps();
            
            // Indexes
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
