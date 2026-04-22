<?php

namespace App\Models;

use App\Enums\TriageScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Triage extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'heart_rate',
        'temperature',
        'respiratory_rate',
        'spo2',
        'weight',
        'height',
        'bmi',
        'triage_score',
        'department_id',
        'notes',
        'triaged_by',
        'triaged_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature'   => 'decimal:1',
            'weight'        => 'decimal:1',
            'height'        => 'decimal:1',
            'bmi'           => 'decimal:1',
            'triage_score'  => TriageScore::class,
            'triaged_at'    => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function triagedBy()
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getBloodPressureAttribute(): ?string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return $this->blood_pressure_systolic . '/' . $this->blood_pressure_diastolic;
        }

        return null;
    }
}
