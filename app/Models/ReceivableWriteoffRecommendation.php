<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableWriteoffRecommendation extends Model
{
    protected $fillable = [
        'receivable_case_id', 'source_type', 'source_id', 'recommended_amount',
        'reason', 'status', 'recommended_by', 'recommended_at', 'approved_by',
        'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
        'linked_writeoff_id', 'linked_credit_note_id', 'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return ['recommended_amount' => 'decimal:2', 'recommended_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime'];
    }

    public function receivableCase(): BelongsTo
    {
        return $this->belongsTo(ReceivableCase::class, 'receivable_case_id');
    }
}
