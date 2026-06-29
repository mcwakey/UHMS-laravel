<?php

namespace App\Models;

use App\Enums\JourneyDelayCause;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Aggregate operational-flow snapshot (Phase 9.8). Stores counts only — no
 * patient-identifiable data.
 */
class JourneyFlowSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date', 'snapshot_hour', 'granularity',
        'from_department_id', 'from_department_type', 'to_department_id', 'to_department_type',
        'cause', 'stage', 'sla_status', 'assignment_status', 'escalation_level',
        'handoff_count', 'breached_count', 'critical_breach_count',
        'unassigned_count', 'assigned_count', 'acknowledged_count', 'resolved_count',
        'total_elapsed_minutes', 'total_time_to_acknowledge_minutes', 'total_time_to_resolve_minutes',
    ];

    // snapshot_date stays a plain Y-m-d string so the upsert match value equals the
    // stored value (a date cast would persist a 00:00:00 time and break idempotency).
    protected $casts = [
        'snapshot_hour' => 'integer',
        'cause' => JourneyDelayCause::class,
    ];

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        // whereDate (not whereBetween) so a same-day upper bound includes rows whose
        // date cast persisted a 00:00:00 time component.
        return $query->whereDate('snapshot_date', '>=', $from)->whereDate('snapshot_date', '<=', $to);
    }

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where(fn ($q) => $q->where('from_department_id', $departmentId)->orWhere('to_department_id', $departmentId));
    }

    public function scopeFromDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('from_department_id', $departmentId);
    }

    public function scopeToDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('to_department_id', $departmentId);
    }

    public function scopeForCause(Builder $query, string $cause): Builder
    {
        return $query->where('cause', $cause);
    }

    public function scopeForGranularity(Builder $query, string $granularity): Builder
    {
        return $query->where('granularity', $granularity);
    }
}
