<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnaesthesiaNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_request_id', 'anaesthetist_id',
        'anaesthesia_type', 'pre_assessment', 'drugs_used', 'dosage_notes',
        'airway_management', 'monitoring_notes', 'complications',
        'start_time', 'end_time', 'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public const TYPES = ['local', 'regional', 'spinal', 'general', 'sedation', 'other'];

    public function procedureRequest(): BelongsTo
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function anaesthetist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anaesthetist_id');
    }
}
