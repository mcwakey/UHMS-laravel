<?php

namespace App\Models;

use App\Enums\FrontDesk\ShiftHandoverStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Front Desk shift handover (Phase 18E). Non-clinical; the snapshot holds safe
 * operational counts/ids only.
 */
class FrontDeskShiftHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_date', 'shift_name', 'handover_started_at', 'handover_completed_at',
        'outgoing_user_id', 'incoming_user_id', 'department_id', 'status',
        'visitors_inside_count', 'pending_callbacks_count', 'pending_couriers_count',
        'open_incidents_count', 'lost_found_unclaimed_count',
        'summary_notes', 'open_items_snapshot',
        'submitted_by', 'submitted_at', 'accepted_by', 'accepted_at',
        'cancelled_by', 'cancelled_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
            'handover_started_at' => 'datetime',
            'handover_completed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => ShiftHandoverStatus::class,
            'open_items_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    /* ── Relationships ───────────────────────────────────────────── */

    public function outgoingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'outgoing_user_id');
    }

    public function incomingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incoming_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /* ── Scopes ──────────────────────────────────────────────────── */

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('shift_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('shift_date', '<=', $to);
        }

        return $query;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $departmentId ? $query->where('department_id', $departmentId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('shift_name', 'like', "%{$term}%")
                ->orWhereHas('outgoingUser', fn (Builder $u) => $u->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%"));
        });
    }

    /** Not yet accepted or cancelled (draft or submitted). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [ShiftHandoverStatus::DRAFT->value, ShiftHandoverStatus::SUBMITTED->value]);
    }

    public function scopePendingAcceptance(Builder $query): Builder
    {
        return $query->where('status', ShiftHandoverStatus::SUBMITTED->value);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function isOpen(): bool
    {
        return in_array($this->status, [ShiftHandoverStatus::DRAFT, ShiftHandoverStatus::SUBMITTED], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === ShiftHandoverStatus::ACCEPTED;
    }

    public function isDraft(): bool
    {
        return $this->status === ShiftHandoverStatus::DRAFT;
    }
}
