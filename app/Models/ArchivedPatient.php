<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedPatient extends Model
{
    protected $fillable = [
        'patient_id',
        'patient_number',
        'full_name',
        'status_before_archive',
        'last_activity_at',
        'archived_at',
        'reason',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'archived_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
