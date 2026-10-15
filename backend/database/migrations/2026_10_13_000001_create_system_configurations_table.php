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
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('snapshot_name')->unique(); // e.g. "Baseline Config v1.0", "Strict Zero-Trust Mode"
            $table->text('description')->nullable();
            
            // Security Configuration Parameters (Restorable without touching patient data)
            $table->integer('session_lifetime_minutes')->default(30);
            $table->integer('max_login_attempts')->default(5);
            $table->integer('lockout_duration_minutes')->default(15);
            $table->boolean('enforce_2fa_for_all_roles')->default(true);
            $table->string('pepper_version', 10)->default('v1');
            $table->boolean('ip_rate_limiting_enabled')->default(true);
            $table->boolean('maintenance_mode')->default(false);
            $table->string('created_by')->default('Chief Medical Director');
            $table->boolean('is_active')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
