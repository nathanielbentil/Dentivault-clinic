<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_code',
        'name',
        'age',
        'gender',
        'phone',
        'medical_history',
        'tooth_number',
        'procedure',
        'clinical_notes',
        'attending_dentist',
        'status',
    ];
}
