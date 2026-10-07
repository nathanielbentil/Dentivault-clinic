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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255)->unique();
            $table->enum('backup_type', ['full_database', 'schema_only', 'data_only'])->default('full_database');
            $table->enum('storage_location', ['local', 'supabase_s3', 'both'])->default('local');
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('sha256_checksum', 64);
            $table->boolean('is_encrypted')->default(true); // age X25519 encryption
            $table->string('age_recipient_preview', 30)->nullable(); // Public key prefix for audit
            $table->enum('status', ['in_progress', 'completed', 'failed', 'verified_restorable'])->default('in_progress')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('restoration_tested_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
