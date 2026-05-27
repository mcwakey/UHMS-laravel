<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispensingRecord extends Model
{
    protected $fillable = [
        'prescription_id',
        'prescription_item_id',
        'patient_id',
        'visit_id',
        'quantity_dispensed',
        'dispensed_by',
        'dispensed_at',
        'notes',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function medicationOrder(): BelongsTo
    {
        return $this->belongsTo(MedicationOrder::class, 'prescription_item_id', 'prescription_item_id');
    }
}
