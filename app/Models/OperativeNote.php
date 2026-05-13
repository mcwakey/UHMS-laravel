<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperativeNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_request_id', 'surgeon_id', 'assistant_surgeon_id',
        'procedure_performed', 'pre_op_diagnosis', 'post_op_diagnosis',
        'findings', 'incision', 'technique', 'blood_loss',
        'complications', 'specimens', 'implants',
        'start_time', 'end_time', 'outcome', 'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function procedureRequest(): BelongsTo
    {
        return $this->belongsTo(ProcedureRequest::class);
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surgeon_id');
    }

    public function assistantSurgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_surgeon_id');
    }
}
