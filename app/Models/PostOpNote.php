<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostOpNote extends Model
{
    use HasFactory;

    protected $table = 'post_op_notes';

    protected $fillable = [
        'procedure_request_id', 'recorded_by',
        'recovery_status', 'pain_score', 'consciousness_level',
        'post_op_instructions', 'medications', 'complications',
        'transfer_destination', 'notes',
    ];

    public const DESTINATIONS = ['ward', 'icu', 'outpatient', 'emergency_obs', 'recovery_room'];

    public function procedureRequest(): BelongsTo
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
