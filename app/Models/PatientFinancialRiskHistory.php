<?php

namespace App\Models;

use App\Enums\PatientFinancialRiskEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable history entry for a patient financial-risk profile (Payment Timing
 * Policy Phase 5). Append-only: the application never exposes edit/delete.
 */
class PatientFinancialRiskHistory extends Model
{
    protected $table = 'patient_financial_risk_history';

    public $timestamps = true;

    protected $fillable = [
        'patient_financial_risk_profile_id',
        'patient_id',
        'event_type',
        'old_values',
        'new_values',
        'reason',
        'performed_by',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => PatientFinancialRiskEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PatientFinancialRiskProfile::class, 'patient_financial_risk_profile_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
