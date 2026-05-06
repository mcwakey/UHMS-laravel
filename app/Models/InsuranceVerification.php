<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_insurance_id',
        'insurance_provider_id',
        'visit_id',
        'driver',
        'status',
        'reference_code',
        'membership_number',
        'member_name',
        'expires_at',
        'verified_by',
        'verified_at',
        'payload',
        'message',
    ];

    protected $casts = [
        'status' => VerificationStatus::class,
        'expires_at' => 'date',
        'verified_at' => 'datetime',
        'payload' => 'array',
    ];

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isAcceptable(): bool
    {
        return $this->status?->isAcceptable() ?? false;
    }
}
