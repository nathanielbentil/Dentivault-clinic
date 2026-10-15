<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_code',
        'name',
        'age',
        'gender',
        'blood_group',
        'phone',
        'email',
        'ward_room',
        'admission_status',
        'diagnosis',
        'treatment_plan',
        'prescription',
        'allergies',
        'medical_history',
        'vitals_summary',
        'attending_doctor',
        'assigned_nurse',
    ];
}
