<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDailyConsumableCharge extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_BILLED = 'BILLED';
    public const STATUS_ENDED = 'ENDED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'admission_id', 'visit_id', 'patient_id', 'bed_id', 'ward_id', 'started_at', 'ended_at',
        'billing_unit', 'quantity', 'service_id', 'invoice_item_id', 'status', 'created_by', 'ended_by', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'quantity' => 'decimal:2',
    ];
}
