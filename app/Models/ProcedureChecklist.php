<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_request_id',
        'consent_signed', 'fasting_confirmed', 'allergies_checked',
        'blood_available', 'site_marked', 'equipment_ready',
        'anaesthesia_review_done', 'pre_op_diagnosis', 'notes',
        'completed_by', 'completed_at',
    ];

    protected $casts = [
        'consent_signed'          => 'boolean',
        'fasting_confirmed'       => 'boolean',
        'allergies_checked'       => 'boolean',
        'blood_available'         => 'boolean',
        'site_marked'             => 'boolean',
        'equipment_ready'         => 'boolean',
        'anaesthesia_review_done' => 'boolean',
        'completed_at'            => 'datetime',
    ];

    public function procedureRequest(): BelongsTo
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
