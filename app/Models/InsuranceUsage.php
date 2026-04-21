<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceUsage extends Model
{
    protected $fillable = [
        'patient_insurance_id',
        'visit_id',
        'invoice_id',
        'amount_covered',
        'patient_amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_covered' => 'decimal:2',
            'patient_amount' => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ── Scopes ───────────────────────────────────────

    public function scopeForInsurance($query, int $patientInsuranceId)
    {
        return $query->where('patient_insurance_id', $patientInsuranceId);
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    public function scopeThisYear($query)
    {
        return $query->where('created_at', '>=', now()->startOfYear());
    }

    public function scopeForVisit($query, int $visitId)
    {
        return $query->where('visit_id', $visitId);
    }
}
