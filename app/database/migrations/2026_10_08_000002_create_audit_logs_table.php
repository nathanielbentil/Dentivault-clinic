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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Event Details
            $table->string('event_type', 80)->index(); // auth.login, auth.failed, auth.lockout, rbac.granted, backup.created
            $table->enum('severity', ['info', 'notice', 'warning', 'critical', 'emergency'])->default('info')->index();
            $table->string('description', 255);
            
            // Request Forensics
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable(); // Contextual metadata (sanitized, no raw passwords)

            // Tamper-Evident Cryptographic Hash Chaining (SHA-256)
            $table->string('prev_hash', 64)->nullable();
            $table->string('log_hash', 64)->index();

            // Append-Only timestamp (no updated_at allowed)
            $table->timestamp('created_at')->useCurrent()->index();

            // Compound index for timeline auditing
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
