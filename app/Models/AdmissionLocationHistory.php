<?php

namespace App\Models;

use App\Enums\AdmissionLocationEvent;
use Illuminate\Database\Eloquent\Model;

class AdmissionLocationHistory extends Model
{
    protected $fillable = [
        'admission_id',
        'patient_id',
        'visit_id',
        'event_type',
        'from_ward_id',
        'from_bed_id',
        'to_ward_id',
        'to_bed_id',
        'moved_by',
        'moved_at',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => AdmissionLocationEvent::class,
            'moved_at' => 'datetime',
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

    public function fromWard()
    {
        return $this->belongsTo(Ward::class, 'from_ward_id');
    }

    public function fromBed()
    {
        return $this->belongsTo(Bed::class, 'from_bed_id');
    }

    public function toWard()
    {
        return $this->belongsTo(Ward::class, 'to_ward_id');
    }

    public function toBed()
    {
        return $this->belongsTo(Bed::class, 'to_bed_id');
    }

    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
