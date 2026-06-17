<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableDispute extends Model
{
    protected $fillable = [
        'receivable_case_id', 'source_type', 'source_id', 'dispute_reason',
        'disputed_amount', 'status', 'raised_by', 'raised_at', 'resolved_by',
        'resolved_at', 'resolution_note', 'recommended_action', 'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'disputed_amount' => 'decimal:2',
            'raised_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function receivableCase(): BelongsTo
    {
        return $this->belongsTo(ReceivableCase::class, 'receivable_case_id');
    }
}
