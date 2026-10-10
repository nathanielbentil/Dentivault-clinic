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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_code')->unique(); // e.g. DENT-1001
            $table->string('name');
            $table->integer('age');
            $table->string('gender', 10);
            $table->string('phone', 30)->nullable();
            $table->string('medical_history')->nullable(); // Allergies, Conditions
            $table->string('tooth_number', 20)->nullable(); // e.g. #14 Upper Left Molar
            $table->string('procedure')->nullable(); // e.g. Composite Filling, Root Canal
            $table->text('clinical_notes')->nullable();
            $table->string('attending_dentist')->nullable();
            $table->enum('status', ['Scheduled', 'In Treatment', 'Completed'])->default('Scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
