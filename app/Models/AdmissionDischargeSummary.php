<?php

namespace App\Models;

use App\Enums\AdmissionDischargeSummaryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionDischargeSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'visit_id',
        'prepared_by',
        'approved_by',
        'approved_at',
        'primary_diagnosis',
        'secondary_diagnoses',
        'admission_reason',
        'hospital_course',
        'investigations_summary',
        'procedures_summary',
        'treatment_given',
        'discharge_condition',
        'discharge_medications',
        'follow_up_instructions',
        'follow_up_date',
        'warning_signs',
        'final_outcome',
        'summary_status',
    ];

    protected function casts(): array
    {
        return [
            'secondary_diagnoses' => 'array',
            'summary_status' => AdmissionDischargeSummaryStatus::class,
            'approved_at' => 'datetime',
            'follow_up_date' => 'date',
        ];
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'admission_discharge_summary',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
