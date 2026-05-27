<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyCaseLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_case_id',
        'visit_id',
        'patient_id',
        'action',
        'title',
        'description',
        'source_type',
        'source_id',
        'performed_by',
    ];

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
