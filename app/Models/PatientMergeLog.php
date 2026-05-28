<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMergeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_merge_request_id',
        'main_patient_id',
        'duplicate_patient_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'performed_by',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function mergeRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PatientMergeRequest::class, 'patient_merge_request_id');
    }

    public function mainPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'main_patient_id');
    }

    public function duplicatePatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'duplicate_patient_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
