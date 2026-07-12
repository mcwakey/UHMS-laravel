<?php

namespace App\Models;

use App\Enums\VisitPaymentPolicyEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable history entry for a visit-payment-policy record (Payment Timing
 * Policy Phase 6). Append-only: the application never exposes edit/delete.
 */
class VisitPaymentPolicyHistory extends Model
{
    protected $table = 'visit_payment_policy_history';

    protected $fillable = [
        'visit_payment_policy_id',
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
            'event_type' => VisitPaymentPolicyEvent::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(VisitPaymentPolicy::class, 'visit_payment_policy_id');
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
