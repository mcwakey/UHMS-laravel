<?php

namespace App\Models;

use App\Enums\FrontDesk\CallCategory;
use App\Enums\FrontDesk\CallDirection;
use App\Enums\FrontDesk\CallOutcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Front Desk call log (Phase 18A). Incoming / outgoing calls handled by
 * reception. Patient link is optional; sensitive notes are only shown to users
 * with the appropriate permission (never in list views).
 */
class FrontDeskCallLog extends Model
{
    use HasFactory;

    public const FOLLOW_UP_PENDING = 'pending';
    public const FOLLOW_UP_COMPLETED = 'completed';
    public const FOLLOW_UP_CANCELLED = 'cancelled';

    protected $fillable = [
        'direction',
        'caller_name',
        'recipient_name',
        'phone_number',
        'department_id',
        'related_patient_id',
        'related_visit_id',
        'category',
        'handled_by',
        'started_at',
        'ended_at',
        'outcome',
        'follow_up_required',
        'follow_up_status',
        'assigned_follow_up_user_id',
        'follow_up_due_at',
        'follow_up_completed_at',
        'follow_up_completed_by',
        'follow_up_cancelled_at',
        'follow_up_cancelled_by',
        'transfer_department_id',
        'transferred_to_user_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'direction' => CallDirection::class,
            'category' => CallCategory::class,
            'outcome' => CallOutcome::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'follow_up_required' => 'boolean',
            'follow_up_due_at' => 'datetime',
            'follow_up_completed_at' => 'datetime',
            'follow_up_cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'related_patient_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'related_visit_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function assignedFollowUpUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_follow_up_user_id');
    }

    public function followUpCompletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_completed_by');
    }

    public function followUpCancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_cancelled_by');
    }

    public function transferDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'transfer_department_id');
    }

    public function transferredToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('caller_name', 'like', "%{$term}%")
                ->orWhere('recipient_name', 'like', "%{$term}%")
                ->orWhere('phone_number', 'like', "%{$term}%")
                ->orWhereHas('patient', function (Builder $pq) use ($term) {
                    $pq->where('patient_number', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
        });
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('started_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('started_at', '<=', $to);
        }

        return $query;
    }

    public function scopeDirection(Builder $query, ?string $direction): Builder
    {
        return $direction ? $query->where('direction', $direction) : $query;
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        return $category ? $query->where('category', $category) : $query;
    }

    public function scopeOutcome(Builder $query, ?string $outcome): Builder
    {
        return $outcome ? $query->where('outcome', $outcome) : $query;
    }

    public function scopeDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $departmentId ? $query->where('department_id', $departmentId) : $query;
    }

    /** Calls flagged for follow-up that have not yet been completed/cancelled. */
    public function scopePendingFollowUp(Builder $query): Builder
    {
        return $query->where('follow_up_required', true)
            ->where(function (Builder $q) {
                $q->whereNull('follow_up_status')
                    ->orWhere('follow_up_status', self::FOLLOW_UP_PENDING);
            });
    }

    /** Alias used by the Phase 18C callback queue. */
    public function scopePendingCallback(Builder $query): Builder
    {
        return $query->pendingFollowUp();
    }

    /** Pending callbacks whose due time has passed (plus the grace minutes). */
    public function scopeOverdueCallback(Builder $query): Builder
    {
        $grace = (int) config('front_desk.calls.callback_overdue_minutes', 0);

        return $query->pendingCallback()
            ->whereNotNull('follow_up_due_at')
            ->where('follow_up_due_at', '<', now()->subMinutes($grace));
    }

    /** Pending callbacks due today. */
    public function scopeDueTodayCallback(Builder $query): Builder
    {
        return $query->pendingCallback()
            ->whereNotNull('follow_up_due_at')
            ->whereDate('follow_up_due_at', today());
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_follow_up_user_id', $userId);
    }

    public function scopeFollowUpStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('follow_up_status', $status) : $query;
    }

    public function scopeTransferred(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull('transfer_department_id')->orWhereNotNull('transferred_to_user_id');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function hasPendingFollowUp(): bool
    {
        return $this->follow_up_required
            && in_array($this->follow_up_status, [null, self::FOLLOW_UP_PENDING], true);
    }

    public function isOverdueCallback(): bool
    {
        $grace = (int) config('front_desk.calls.callback_overdue_minutes', 0);

        return $this->hasPendingFollowUp()
            && $this->follow_up_due_at !== null
            && $this->follow_up_due_at->lt(now()->subMinutes($grace));
    }

    public function isTransferred(): bool
    {
        return $this->transfer_department_id !== null || $this->transferred_to_user_id !== null;
    }

    public function followUpNote(): ?string
    {
        return $this->metadata['follow_up_note'] ?? null;
    }

    public function completionNote(): ?string
    {
        return $this->metadata['completion_note'] ?? null;
    }

    public function cancellationReason(): ?string
    {
        return $this->metadata['cancellation_reason'] ?? null;
    }
}
