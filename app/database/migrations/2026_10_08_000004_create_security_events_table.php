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
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('attack_type', ['sqli', 'xss', 'csrf_violation', 'brute_force', 'unauthorized_access', 'tamper_detected'])->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10);
            $table->string('url_path', 255);
            $table->json('payload_snippet')->nullable(); // Sanitized snippet of the hostile payload for forensics
            $table->boolean('blocked')->default(true);
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['attack_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
