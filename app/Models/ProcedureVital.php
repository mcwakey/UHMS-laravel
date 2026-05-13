<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureVital extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_request_id', 'stage',
        'temperature', 'blood_pressure', 'pulse', 'respiratory_rate',
        'oxygen_saturation', 'weight', 'pain_score', 'notes',
        'recorded_by', 'recorded_at',
    ];

    protected $casts = ['recorded_at' => 'datetime'];

    public const STAGES = ['pre_op', 'intra_op', 'post_op', 'recovery'];

    public function procedureRequest(): BelongsTo
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
