<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_date',
        'started_at',
        'ended_at',
        'opening_balance',
        'expected_closing',
        'actual_closing',
        'variance',
        'notes',
        'status',
        'verified_by',
    ];

    protected $casts = [
        'status' => ShiftStatus::class,
        'shift_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'expected_closing' => 'decimal:2',
        'actual_closing' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Scopes ──

    public function scopeOpen($query)
    {
        return $query->where('status', ShiftStatus::OPEN);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->where('shift_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('shift_date', '<=', $to));
    }

    // ── Accessors ──

    public function getIsOpenAttribute(): bool
    {
        return $this->status === ShiftStatus::OPEN;
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->ended_at) {
            return null;
        }

        return $this->started_at->diffForHumans($this->ended_at, true);
    }
}
