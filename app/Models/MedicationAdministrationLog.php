<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationAdministrationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'medication_administration_id',
        'medication_order_id',
        'schedule_id',
        'action',
        'old_value',
        'new_value',
        'reason',
        'performed_by',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function administration()
    {
        return $this->belongsTo(MedicationAdministration::class, 'medication_administration_id');
    }

    public function medicationOrder()
    {
        return $this->belongsTo(MedicationOrder::class);
    }

    public function schedule()
    {
        return $this->belongsTo(MedicationAdministrationSchedule::class, 'schedule_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
