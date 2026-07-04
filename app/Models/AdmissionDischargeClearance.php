<?php

namespace App\Models;

use App\Enums\AdmissionDischargeClearanceStatus;
use App\Enums\AdmissionDischargeClearanceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionDischargeClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'visit_id',
        'clearance_type',
        'status',
        'cleared_by',
        'cleared_at',
        'revoked_by',
        'revoked_at',
        'note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'clearance_type' => AdmissionDischargeClearanceType::class,
            'status' => AdmissionDischargeClearanceStatus::class,
            'cleared_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
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

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'admission_discharge_clearance',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
