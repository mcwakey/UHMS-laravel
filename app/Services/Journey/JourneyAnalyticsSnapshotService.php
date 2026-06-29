<?php

namespace App\Services\Journey;

use App\Enums\JourneyDelayCause;
use App\Models\JourneyFlowSnapshot;
use App\Models\JourneyHandoffAssignment;
use Illuminate\Support\Carbon;

/**
 * Builds lightweight AGGREGATE flow snapshots from live handoff data + assignment
 * lifecycle metadata. No patient-level storage. Idempotent (upsert by bucket key).
 *
 * Two row kinds share the table, distinguished by sla_status:
 *  - active-state rows (within/near_breach/breached/critical_breach): point-in-time
 *    pressure — only meaningful for "today", so skipped for past dates.
 *  - resolved-lifecycle rows (sla_status = 'resolved'): the day's resolved
 *    assignments — accurate historically (keyed on resolved_at date).
 */
class JourneyAnalyticsSnapshotService
{
    public const RESOLVED_BUCKET = 'resolved';

    public function __construct(private JourneyHandoffWorklistService $worklist) {}

    /** @return list<array<string, mixed>> */
    public function snapshotForDate(Carbon $date, string $granularity = 'day'): array
    {
        $rows = [];

        // Point-in-time active pressure can only be captured for the current day.
        if ($date->isToday()) {
            foreach ($this->worklist->aggregateActiveHandoffs() as $bucket) {
                $rows[] = array_merge($this->emptyRow($date, $granularity), $bucket);
            }
        }

        foreach ($this->resolvedLifecycle($date) as $bucket) {
            $rows[] = array_merge($this->emptyRow($date, $granularity), $bucket);
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function snapshotForRange(Carbon $from, Carbon $to, string $granularity = 'day'): array
    {
        $all = [];
        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $all = array_merge($all, $this->snapshotForDate($date->copy(), $granularity));
        }

        return $all;
    }

    /** Idempotent upsert by the unique bucket key. */
    public function upsertSnapshot(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            JourneyFlowSnapshot::updateOrCreate(
                [
                    'snapshot_date' => $row['snapshot_date'],
                    'granularity' => $row['granularity'],
                    'from_department_type' => $row['from_department_type'],
                    'to_department_type' => $row['to_department_type'],
                    'cause' => $row['cause'],
                    'sla_status' => $row['sla_status'],
                ],
                $row,
            );
            $count++;
        }

        return $count;
    }

    /** @return list<array<string, mixed>> resolved assignments for the date by (from_type, to_type, cause). */
    private function resolvedLifecycle(Carbon $date): array
    {
        $assignments = JourneyHandoffAssignment::query()
            ->with('fromDepartment:id,type')
            ->where('status', JourneyHandoffAssignment::STATUS_RESOLVED)
            ->whereDate('resolved_at', $date->toDateString())
            ->limit(5000)
            ->get();

        $buckets = [];
        foreach ($assignments as $assignment) {
            $fromType = $this->typeValue($assignment->fromDepartment?->type);
            $toType = $assignment->to_department_type;
            if ($fromType === null || $toType === null) {
                continue;
            }
            $cause = $assignment->cause instanceof JourneyDelayCause ? $assignment->cause->value : (string) $assignment->cause;
            $key = $fromType.'|'.$toType.'|'.$cause;

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'from_department_type' => $fromType, 'to_department_type' => $toType,
                    'cause' => $cause, 'sla_status' => self::RESOLVED_BUCKET,
                    'resolved_count' => 0, 'total_time_to_acknowledge_minutes' => 0, 'total_time_to_resolve_minutes' => 0,
                ];
            }
            $buckets[$key]['resolved_count']++;
            if ($assignment->assigned_at && $assignment->acknowledged_at) {
                $buckets[$key]['total_time_to_acknowledge_minutes'] += max(0, (int) round($assignment->assigned_at->diffInMinutes($assignment->acknowledged_at)));
            }
            if ($assignment->assigned_at && $assignment->resolved_at) {
                $buckets[$key]['total_time_to_resolve_minutes'] += max(0, (int) round($assignment->assigned_at->diffInMinutes($assignment->resolved_at)));
            }
        }

        return array_values($buckets);
    }

    private function emptyRow(Carbon $date, string $granularity): array
    {
        return [
            'snapshot_date' => $date->toDateString(),
            'granularity' => $granularity,
            'from_department_id' => null, 'to_department_id' => null,
            'from_department_type' => null, 'to_department_type' => null,
            'cause' => JourneyDelayCause::UNKNOWN->value, 'stage' => null,
            'sla_status' => JourneySlaService::WITHIN, 'assignment_status' => null, 'escalation_level' => null,
            'handoff_count' => 0, 'breached_count' => 0, 'critical_breach_count' => 0,
            'unassigned_count' => 0, 'assigned_count' => 0, 'acknowledged_count' => 0, 'resolved_count' => 0,
            'total_elapsed_minutes' => 0, 'total_time_to_acknowledge_minutes' => null, 'total_time_to_resolve_minutes' => null,
        ];
    }

    private function typeValue(mixed $type): ?string
    {
        return $type instanceof \App\Enums\DepartmentType ? $type->value : (is_string($type) ? $type : null);
    }
}
