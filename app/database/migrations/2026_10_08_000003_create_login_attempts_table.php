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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email_or_username')->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->boolean('is_successful')->default(false)->index();
            $table->string('failure_reason', 100)->nullable(); // invalid_password, account_locked, totp_invalid
            $table->timestamp('created_at')->useCurrent()->index();

            // Compound index for sliding-window rate limiting & brute-force IP detection
            $table->index(['email_or_username', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
