<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodDonorScreening extends Model
{
    use HasFactory;

    // WHO-style screening lifecycle stages
    public const STAGE_QUESTIONNAIRE_PENDING = 'QUESTIONNAIRE_PENDING';

    public const STAGE_QUESTIONNAIRE_COMPLETED = 'QUESTIONNAIRE_COMPLETED';

    public const STAGE_PHYSICAL_PENDING = 'PHYSICAL_ASSESSMENT_PENDING';

    public const STAGE_PHYSICAL_COMPLETED = 'PHYSICAL_ASSESSMENT_COMPLETED';

    public const STAGE_ELIGIBLE = 'ELIGIBLE';

    public const STAGE_TEMP_DEFERRED = 'TEMPORARILY_DEFERRED';

    public const STAGE_PERM_DEFERRED = 'PERMANENTLY_DEFERRED';

    public const DECISION_ELIGIBLE = 'ELIGIBLE';

    public const DECISION_TEMP_DEFERRED = 'TEMPORARILY_DEFERRED';

    public const DECISION_PERM_DEFERRED = 'PERMANENTLY_DEFERRED';

    public const DECISION_NEEDS_REVIEW = 'NEEDS_REVIEW';

    protected $fillable = [
        'donor_id', 'donation_id', 'stage',
        'questionnaire', 'questionnaire_by', 'questionnaire_at',
        'weight_kg', 'temperature_c', 'pulse', 'bp_systolic', 'bp_diastolic',
        'hemoglobin', 'general_appearance', 'venous_access', 'fitness_notes',
        'assessed_by', 'assessed_at',
        'eligibility_decision', 'suggested_flags', 'deferral_type', 'deferral_reason',
        'deferral_until', 'eligibility_overridden', 'override_reason',
        'reviewed_by', 'reviewed_at',
        'consent_donate', 'consent_testing', 'consent_contact',
        'notes', 'created_by',
    ];

    protected $casts = [
        'questionnaire' => 'array',
        'suggested_flags' => 'array',
        'questionnaire_at' => 'datetime',
        'assessed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'deferral_until' => 'date',
        'weight_kg' => 'decimal:2',
        'temperature_c' => 'decimal:1',
        'hemoglobin' => 'decimal:1',
        'eligibility_overridden' => 'boolean',
        'consent_donate' => 'boolean',
        'consent_testing' => 'boolean',
        'consent_contact' => 'boolean',
    ];

    public function donor()
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }

    public function donation()
    {
        return $this->belongsTo(BloodDonation::class, 'donation_id');
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function questionnaireBy()
    {
        return $this->belongsTo(User::class, 'questionnaire_by');
    }

    public function isEligible(): bool
    {
        return $this->eligibility_decision === self::DECISION_ELIGIBLE || $this->eligibility_overridden;
    }
}
