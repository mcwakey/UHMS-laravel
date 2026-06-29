<?php

namespace App\Models;

use App\Enums\JourneyDelayCause;
use App\Services\Department\DepartmentDashboardCapabilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operational coordination state for one cross-department handoff. Stores ONLY
 * ownership/escalation metadata — the delay/cause/SLA/handoff stay derived from
 * existing records (Phase 9.1–9.4).
 */
class JourneyHandoffAssignment extends Model
{
    public const STATUS_UNASSIGNED = 'unassigned';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    public const ESCALATION_NONE = 'none';

    public const ESCALATION_WARNING = 'warning';

    public const ESCALATION_SUPERVISOR = 'supervisor';

    public const ESCALATION_CRITICAL = 'critical';

    /** Statuses that mean the row is still an open obligation. */
    public const ACTIVE_STATUSES = [self::STATUS_UNASSIGNED, self::STATUS_ASSIGNED, self::STATUS_ACKNOWLEDGED];

    protected $fillable = [
        'visit_id', 'cause', 'from_department_id', 'to_department_id', 'to_department_type',
        'assigned_to_user_id', 'assigned_by_user_id', 'status', 'escalation_level',
        'assigned_at', 'acknowledged_at', 'resolved_at', 'dismissed_at', 'last_escalated_at', 'notes',
    ];

    protected $casts = [
        'cause' => JourneyDelayCause::class,
        'assigned_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'last_escalated_at' => 'datetime',
    ];

    // ---- Relationships -------------------------------------------------

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    // ---- State helpers -------------------------------------------------

    public function isAssigned(): bool
    {
        return $this->assigned_to_user_id !== null && in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_ACKNOWLEDGED], true);
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_DISMISSED], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isEscalated(): bool
    {
        return $this->escalation_level !== self::ESCALATION_NONE && $this->escalation_level !== null;
    }

    public function canBeClaimedBy(?User $user): bool
    {
        if ($user === null || $this->isResolved()) {
            return false;
        }

        return $this->userCanActOnDestination($user);
    }

    public function canBeResolvedBy(?User $user): bool
    {
        if ($user === null || $this->isResolved()) {
            return false;
        }

        return $this->assigned_to_user_id === $user->id || $this->userCanActOnDestination($user);
    }

    /** Stable identity for this handoff (visit + cause + route). */
    public function identityKey(): string
    {
        return self::buildIdentityKey($this->visit_id, $this->cause, $this->from_department_id, $this->to_department_id, $this->to_department_type);
    }

    public static function buildIdentityKey(int $visitId, JourneyDelayCause $cause, ?int $fromDeptId, ?int $toDeptId, ?string $toType): string
    {
        $destination = $toDeptId !== null ? 'd:'.$toDeptId : 't:'.($toType ?? '');

        return implode('|', [$visitId, $cause->value, $fromDeptId ?? '0', $destination]);
    }

    private function userCanActOnDestination(User $user): bool
    {
        $capability = match ($this->to_department_type) {
            'investigation', 'radiology', 'blood_bank' => 'investigation_access',
            'pharmacy' => 'pharmacy_access',
            'inpatient', 'maternity' => 'ward_access',
            'finance', 'administrative' => 'financial_access',
            'consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records' => 'consultation_access',
            default => null,
        };

        return $capability === null || app(DepartmentDashboardCapabilityService::class)->can($user, $capability);
    }
}
