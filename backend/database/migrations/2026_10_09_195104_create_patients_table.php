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
            $table->string('patient_code')->unique(); // e.g. MED-1001
            $table->string('name')->index();
            $table->integer('age');
            $table->string('gender', 10);
            $table->string('blood_group', 10)->default('O+');
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('ward_room', 50)->default('General Ward 1');
            $table->enum('admission_status', ['Admitted', 'Outpatient', 'ICU', 'Discharged'])->default('Outpatient')->index();
            $table->string('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('prescription')->nullable();
            $table->string('allergies')->default('None Reported');
            $table->text('medical_history')->nullable();
            $table->string('vitals_summary')->nullable(); // BP, Pulse, Temp, SpO2
            $table->string('attending_doctor')->nullable()->index();
            $table->string('assigned_nurse')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
