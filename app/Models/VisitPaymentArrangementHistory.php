<?php

namespace App\Models;

use App\Enums\VisitPaymentArrangementEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable history entry for a per-visit payment arrangement (Payment Timing
 * Policy Phase 7). Append-only: the application never exposes edit/delete.
 */
class VisitPaymentArrangementHistory extends Model
{
    protected $table = 'visit_payment_arrangement_history';

    protected $fillable = [
        'visit_payment_arrangement_id',
        'visit_id',
        'event_type',
        'old_values',
        'new_values',
        'reason_code',
        'performed_by',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => VisitPaymentArrangementEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    public function arrangement(): BelongsTo
    {
        return $this->belongsTo(VisitPaymentArrangement::class, 'visit_payment_arrangement_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
