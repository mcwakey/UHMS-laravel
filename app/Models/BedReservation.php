<?php

namespace App\Models;

use App\Enums\BedReservationStatus;
use Illuminate\Database\Eloquent\Model;

class BedReservation extends Model
{
    protected $fillable = [
        'admission_request_id',
        'admission_id',
        'patient_id',
        'visit_id',
        'bed_id',
        'status',
        'reserved_by',
        'released_by',
        'reserved_at',
        'expires_at',
        'released_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => BedReservationStatus::class,
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function admissionRequest()
    {
        return $this->belongsTo(AdmissionRequest::class);
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

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function reservedBy()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', BedReservationStatus::ACTIVE);
    }
}
