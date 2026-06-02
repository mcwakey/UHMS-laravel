<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'blood_request_id', 'patient_id', 'visit_id', 'admission_id', 'emergency_case_id',
        'patient_blood_group', 'patient_rh_factor', 'diagnosis', 'clinical_indication',
        'hemoglobin_level', 'pregnancy_status', 'previous_transfusion_reaction',
        'previous_transfusion_reaction_notes', 'transfusion_history', 'special_requirements',
        'requested_component_type', 'units_required', 'urgency', 'requested_by',
    ];

    protected $casts = [
        'previous_transfusion_reaction' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(BloodRequest::class, 'blood_request_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** Full ABO/Rh group string, e.g. "A+". */
    public function getBloodGroupAttribute(): ?string
    {
        if (! $this->patient_blood_group) {
            return null;
        }

        $abo = $this->patient_blood_group;
        $rh = $this->patient_rh_factor;
        if ($rh && ! str_ends_with($abo, '+') && ! str_ends_with($abo, '-')) {
            return $abo.($rh === 'NEGATIVE' || $rh === '-' ? '-' : '+');
        }

        return $abo;
    }
}
