<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_request_id', 'theatre_room_id',
        'scheduled_start', 'scheduled_end',
        'surgeon_id', 'anaesthetist_id', 'assistant_surgeon_id',
        'theatre_nurse_ids', 'required_equipment',
        'status', 'notes',
        'scheduled_by', 'scheduled_at', 'is_current',
    ];

    protected $casts = [
        'scheduled_start'   => 'datetime',
        'scheduled_end'     => 'datetime',
        'scheduled_at'      => 'datetime',
        'theatre_nurse_ids' => 'array',
        'is_current'        => 'boolean',
    ];

    public function procedureRequest(): BelongsTo
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function theatreRoom(): BelongsTo
    {
        return $this->belongsTo(TheatreRoom::class);
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surgeon_id');
    }

    public function anaesthetist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anaesthetist_id');
    }

    public function assistantSurgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_surgeon_id');
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
