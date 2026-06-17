<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivableCase extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PROMISED = 'promised';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'case_number', 'payer_type', 'payer_id', 'payer_name_snapshot',
        'patient_id', 'insurance_provider_id', 'sponsor_id', 'corporate_client_id', 'claim_id',
        'case_type', 'priority', 'status', 'assigned_to', 'opened_by', 'opened_at',
        'closed_by', 'closed_at', 'closure_reason', 'total_original_amount',
        'total_outstanding_amount', 'total_disputed_amount', 'total_promised_amount',
        'oldest_due_date', 'aging_bucket', 'metadata_snapshot', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'oldest_due_date' => 'date',
            'total_original_amount' => 'decimal:2',
            'total_outstanding_amount' => 'decimal:2',
            'total_disputed_amount' => 'decimal:2',
            'total_promised_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceivableCaseItem::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(ReceivableFollowup::class);
    }

    public function promises(): HasMany
    {
        return $this->hasMany(ReceivablePromise::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(ReceivableDispute::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReceivableAssignment::class);
    }

    public function dunningNotices(): HasMany
    {
        return $this->hasMany(ReceivableDunningNotice::class);
    }

    public function writeoffRecommendations(): HasMany
    {
        return $this->hasMany(ReceivableWriteoffRecommendation::class);
    }

    public function creditnoteRecommendations(): HasMany
    {
        return $this->hasMany(ReceivableCreditnoteRecommendation::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED, 'cancelled']);
    }
}
