<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyDelayCause;
use App\Models\JourneyHandoffAssignment;
use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Department\DepartmentDashboardCapabilityService;
use App\Services\Department\DepartmentSchemaCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Cross-department handoff worklists + SLA matrix. Same bounded shape as the Phase
 * 9.3 worklist: one candidate query, light query-free filtering/aggregation via
 * quickCause + the SLA service, and the full handoff resolver only on the displayed
 * rows. Capability-aware; no journey tables, no broad joins.
 */
class JourneyHandoffWorklistService
{
    public const MAX_ROWS = 50;

    private const CANDIDATE_CAP = 600;

    public function __construct(
        private DepartmentDashboardCapabilityService $capabilities,
        private JourneyHandoffResolver $handoffs,
        private JourneyDelayCauseResolver $causes,
        private JourneySlaService $sla,
        private JourneyEscalationService $escalation,
    ) {}

    /** Actions THIS department must perform for others (TO == this department's domain). */
    public function owedByDepartment(Department $department, User $user, array $filters = []): array
    {
        $type = $this->typeValue($department->type);
        if (! $this->canSeeType($user, $type)) {
            return [];
        }
        $domain = $this->ownerDomainForType($type);

        return $this->build(
            $user,
            $filters,
            light: fn ($row, JourneyDelayCause $cause) => $cause->ownerType() === $domain && (int) $row->from_dept_id !== $department->id,
            final: fn (JourneyHandoff $h) => $this->ownerDomainForType($h->toDepartmentType) === $domain && $h->fromDepartmentId !== $department->id,
            scopeFromDeptId: null,
        );
    }

    /** Actions OTHER departments owe before THIS department's patients can move (FROM == this department). */
    public function owedToDepartment(Department $department, User $user, array $filters = []): array
    {
        $type = $this->typeValue($department->type);
        if (! $this->canSeeType($user, $type)) {
            return [];
        }
        $domain = $this->ownerDomainForType($type);

        return $this->build(
            $user,
            $filters,
            light: fn ($row, JourneyDelayCause $cause) => $cause->ownerType() !== null && $cause->ownerType() !== $domain,
            final: fn (JourneyHandoff $h) => $h->isCrossDepartment() && $this->ownerDomainForType($h->toDepartmentType) !== $domain,
            scopeFromDeptId: $department->id,
        );
    }

    /** Every handoff the user is allowed to see (either side). */
    public function forUser(User $user, array $filters = []): array
    {
        return $this->build(
            $user,
            $filters,
            light: fn ($row, JourneyDelayCause $cause) => $this->userSeesHandoff($user, $row->from_dept_type, $cause->ownerType()),
            final: fn (JourneyHandoff $h) => $this->userSeesHandoff($user, $h->fromDepartmentType, $this->ownerDomainForType($h->toDepartmentType)),
            scopeFromDeptId: null,
        );
    }

    /**
     * Active near-breach+ handoffs that have NO active assignment row — the targets
     * of the unassigned sweep + oversight worklist. Bounded; full-resolves only the
     * top rows after excluding already-tracked visits.
     *
     * @return list<JourneyHandoff>
     */
    public function unassignedBreachedHandoffs(?int $departmentId = null, ?string $cause = null, int $limit = 50): array
    {
        $candidates = [];
        foreach ($this->candidateRows($departmentId) as $row) {
            $causeEnum = $this->causes->quickCause(VisitStatus::tryFrom((string) $row->status), $row->from_dept_type);
            if ($causeEnum === JourneyDelayCause::UNKNOWN || ($cause !== null && $causeEnum->value !== $cause)) {
                continue;
            }
            $elapsed = $this->elapsed($row->entered_at);
            $sla = $this->sla->evaluate($causeEnum, $elapsed);
            if ($this->sla->rank($sla['sla_status']) < 2) {
                continue;
            }
            $candidates[] = ['id' => (int) $row->visit_id, 'rank' => $this->sla->rank($sla['sla_status']) * 1_000_000 + $elapsed];
        }

        usort($candidates, fn ($a, $b) => $b['rank'] <=> $a['rank']);
        $ids = array_slice(array_column($candidates, 'id'), 0, $limit * 2);
        if ($ids === []) {
            return [];
        }

        // Drop visits that already have an active assignment (handled elsewhere).
        $assigned = JourneyHandoffAssignment::whereIn('visit_id', $ids)
            ->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->pluck('visit_id')
            ->flip();
        $ids = array_slice(array_values(array_filter($ids, fn ($id) => ! $assigned->has($id))), 0, $limit);
        if ($ids === []) {
            return [];
        }

        $visits = Visit::with(['patient', 'currentDepartment', 'statusLogs', 'labRequests', 'prescriptions', 'admission'])
            ->whereIn('id', $ids)->get()->keyBy('id');

        $handoffs = [];
        foreach ($ids as $id) {
            $visit = $visits->get($id);
            if ($visit === null) {
                continue;
            }
            $handoff = $this->handoffs->resolve($visit, null);
            if ($this->sla->rank($handoff->slaStatus) >= 2 && $handoff->isCrossDepartment()) {
                $handoff->attachAssignment(null, $this->escalation->levelFor($handoff, null));
                $handoffs[] = $handoff;
            }
        }

        return $handoffs;
    }

    /**
     * Aggregate of ALL active cross-department handoffs by (from_type, to_type, cause,
     * sla_status) for analytics snapshots — one candidate query + one assignment query,
     * grouped in PHP. Includes within-SLA handoffs (not just near-breach+).
     *
     * @return list<array<string, mixed>>
     */
    public function aggregateActiveHandoffs(): array
    {
        $rows = $this->candidateRows(null);
        if ($rows->isEmpty()) {
            return [];
        }

        $assignmentStatus = JourneyHandoffAssignment::query()
            ->whereIn('visit_id', $rows->pluck('visit_id'))
            ->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->pluck('status', 'visit_id');

        $buckets = [];
        foreach ($rows as $row) {
            $cause = $this->causes->quickCause(VisitStatus::tryFrom((string) $row->status), $row->from_dept_type);
            $domain = $cause->ownerType();
            if ($cause === JourneyDelayCause::UNKNOWN || $domain === null) {
                continue;
            }
            $fromType = $row->from_dept_type;
            $toType = $this->ownerTypeToDepartmentType($domain);
            if ($fromType === null || $toType === null || $fromType === $toType) {
                continue; // cross-department only
            }

            $elapsed = $this->elapsed($row->entered_at);
            $sla = $this->sla->evaluate($cause, $elapsed);
            $key = $fromType.'|'.$toType.'|'.$cause->value.'|'.$sla['sla_status'];

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'from_department_type' => $fromType, 'to_department_type' => $toType,
                    'cause' => $cause->value, 'sla_status' => $sla['sla_status'],
                    'handoff_count' => 0, 'breached_count' => 0, 'critical_breach_count' => 0,
                    'total_elapsed_minutes' => 0, 'unassigned_count' => 0, 'assigned_count' => 0, 'acknowledged_count' => 0,
                ];
            }
            $buckets[$key]['handoff_count']++;
            $buckets[$key]['total_elapsed_minutes'] += $elapsed;
            if ($this->sla->isBreached($sla['sla_status'])) {
                $buckets[$key]['breached_count']++;
            }
            if ($sla['sla_status'] === JourneySlaService::CRITICAL_BREACH) {
                $buckets[$key]['critical_breach_count']++;
            }
            $status = $assignmentStatus[$row->visit_id] ?? null;
            if ($status === JourneyHandoffAssignment::STATUS_ACKNOWLEDGED) {
                $buckets[$key]['acknowledged_count']++;
            } elseif ($status === JourneyHandoffAssignment::STATUS_ASSIGNED) {
                $buckets[$key]['assigned_count']++;
            } else {
                $buckets[$key]['unassigned_count']++;
            }
        }

        return array_values($buckets);
    }

    /** Count of active handoffs assigned to the user (one cheap indexed count). */
    public function myActiveAssignmentCount(User $user): int
    {
        return JourneyHandoffAssignment::query()
            ->where('assigned_to_user_id', $user->id)
            ->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->count();
    }

    /**
     * Light summary (counts) for the user's own department — query-free aggregation.
     *
     * @return array{owed_by:int,owed_to:int,breached:int,top_counterpart:?string}
     */
    public function summaryForUser(User $user): array
    {
        $myType = $this->typeValue($user->department?->type);
        $myDomain = $this->ownerDomainForType($myType);
        $owedBy = 0;
        $owedTo = 0;
        $breached = 0;
        $critical = 0;
        $supervisor = 0;
        $counterparts = [];

        foreach ($this->lightHandoffs(null) as $hand) {
            if (! $this->userSeesHandoff($user, $hand['from_type'], $hand['to_domain'])) {
                continue;
            }
            $toDomain = $hand['to_domain'];
            $fromDomain = $this->ownerDomainForType($hand['from_type']);
            $relevant = false;

            if ($myDomain !== null && $toDomain === $myDomain && $fromDomain !== $myDomain) {
                $owedBy++;
                $counterparts[$hand['from_type']] = ($counterparts[$hand['from_type']] ?? 0) + 1;
                $relevant = true;
            } elseif ($myDomain !== null && $fromDomain === $myDomain && $toDomain !== $myDomain) {
                $owedTo++;
                $counterparts[$this->ownerTypeToDepartmentType($toDomain)] = ($counterparts[$this->ownerTypeToDepartmentType($toDomain)] ?? 0) + 1;
                $relevant = true;
            }

            if ($relevant) {
                $breached += $hand['breached'] ? 1 : 0;
                $critical += $hand['sla_status'] === JourneySlaService::CRITICAL_BREACH ? 1 : 0;
                $supervisor += $hand['sla_status'] === JourneySlaService::BREACHED ? 1 : 0;
            }
        }

        arsort($counterparts);

        return [
            'owed_by' => $owedBy,
            'owed_to' => $owedTo,
            'breached' => $breached,
            'critical_escalations' => $critical,
            'supervisor_escalations' => $supervisor,
            'top_counterpart' => array_key_first($counterparts),
        ];
    }

    /**
     * Light handoff insight for a specific dashboard department — query-free, and runs
     * ZERO queries for non-journey departments (stores/admin). owed_by is domain-level
     * (the resolving department's domain); owed_to is scoped to this department's
     * outgoing waits.
     *
     * @return array{owed_by:int,owed_to:int,breached:int,top_counterpart:?string}
     */
    public function summaryForContext(?int $departmentId, ?string $type, User $user): array
    {
        $empty = ['owed_by' => 0, 'owed_to' => 0, 'breached' => 0, 'top_counterpart' => null];
        $domain = $this->ownerDomainForType($type);
        if ($departmentId === null || $domain === null || ! $this->canSeeType($user, $type)) {
            return $empty;
        }

        $owedBy = 0;
        $owedTo = 0;
        $breached = 0;
        $critical = 0;
        $supervisor = 0;
        $nearBreach = 0;
        $counterparts = [];

        foreach ($this->lightHandoffs(null) as $hand) {
            $toDomain = $hand['to_domain'];
            $fromDomain = $this->ownerDomainForType($hand['from_type']);
            $relevant = false;

            if ($toDomain === $domain && $hand['from_dept_id'] !== $departmentId && $fromDomain !== $domain) {
                $owedBy++;
                $counterparts[$hand['from_type']] = ($counterparts[$hand['from_type']] ?? 0) + 1;
                $relevant = true;
            } elseif ($hand['from_dept_id'] === $departmentId && $toDomain !== $domain) {
                $owedTo++;
                $counterparts[$this->ownerTypeToDepartmentType($toDomain)] = ($counterparts[$this->ownerTypeToDepartmentType($toDomain)] ?? 0) + 1;
                $relevant = true;
            }

            if ($relevant) {
                $breached += $hand['breached'] ? 1 : 0;
                // Derived escalation (unassigned worst case) — query-free dashboard signal.
                $critical += $hand['sla_status'] === JourneySlaService::CRITICAL_BREACH ? 1 : 0;
                $supervisor += $hand['sla_status'] === JourneySlaService::BREACHED ? 1 : 0;
                $nearBreach += $hand['sla_status'] === JourneySlaService::NEAR_BREACH ? 1 : 0;
            }
        }

        arsort($counterparts);

        // Routing quality signal (Phase 9.7): does this department have a supervisor?
        $supervisorMissing = ! \App\Models\Department::whereKey($departmentId)->whereNotNull('supervisor_user_id')->exists();

        return [
            'owed_by' => $owedBy,
            'owed_to' => $owedTo,
            'breached' => $breached,
            'critical_escalations' => $critical,
            'supervisor_escalations' => $supervisor,
            'near_breach' => $nearBreach,
            'supervisor_missing' => $supervisorMissing,
            'top_counterpart' => array_key_first($counterparts),
        ];
    }

    /**
     * From → To SLA matrix the user is allowed to see (query-free aggregation).
     *
     * @return list<array{from:string,to:string,count:int,breached:int,avg_wait:int}>
     */
    public function matrixForUser(User $user): array
    {
        $cells = [];
        foreach ($this->lightHandoffs(null) as $hand) {
            if (! $this->userSeesHandoff($user, $hand['from_type'], $hand['to_domain'])) {
                continue;
            }
            $toType = $this->ownerTypeToDepartmentType($hand['to_domain']);
            if ($hand['from_type'] === null || $toType === null || $hand['from_type'] === $toType) {
                continue;
            }
            $key = $hand['from_type'].'>'.$toType;
            $cells[$key] ??= ['from' => $hand['from_type'], 'to' => $toType, 'count' => 0, 'breached' => 0, 'wait_sum' => 0];
            $cells[$key]['count']++;
            $cells[$key]['breached'] += $hand['breached'] ? 1 : 0;
            $cells[$key]['wait_sum'] += $hand['elapsed'];
        }

        $matrix = array_map(fn ($cell) => [
            'from' => $cell['from'],
            'to' => $cell['to'],
            'count' => $cell['count'],
            'breached' => $cell['breached'],
            'avg_wait' => $cell['count'] > 0 ? (int) round($cell['wait_sum'] / $cell['count']) : 0,
        ], array_values($cells));

        usort($matrix, fn ($a, $b) => [$b['breached'], $b['count']] <=> [$a['breached'], $a['count']]);

        return $matrix;
    }

    // ------------------------------------------------------------------

    /**
     * @return list<JourneyHandoff>
     */
    private function build(User $user, array $filters, callable $light, callable $final, ?int $scopeFromDeptId): array
    {
        $candidates = [];
        foreach ($this->candidateRows($scopeFromDeptId) as $row) {
            $cause = $this->causes->quickCause(VisitStatus::tryFrom((string) $row->status), $row->from_dept_type);
            if ($cause === JourneyDelayCause::UNKNOWN || ! $light($row, $cause)) {
                continue;
            }
            $elapsed = $this->elapsed($row->entered_at);
            $sla = $this->sla->evaluate($cause, $elapsed);
            if ($this->sla->rank($sla['sla_status']) < 2) { // include near-breach and worse
                continue;
            }
            $candidates[] = ['id' => (int) $row->visit_id, 'rank' => $this->sla->rank($sla['sla_status']) * 1_000_000 + $elapsed];
        }

        usort($candidates, fn ($a, $b) => $b['rank'] <=> $a['rank']);
        $ids = array_slice(array_column($candidates, 'id'), 0, self::MAX_ROWS);
        if ($ids === []) {
            return [];
        }

        $visits = Visit::with(['patient', 'currentDepartment', 'statusLogs', 'labRequests', 'prescriptions', 'admission'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $handoffs = [];
        foreach ($ids as $id) {
            $visit = $visits->get($id);
            if ($visit === null) {
                continue;
            }
            $handoff = $this->handoffs->resolve($visit, $user);
            if (! $final($handoff) || ! $this->passesFilters($handoff, $filters)) {
                continue;
            }
            $handoffs[] = $handoff;
        }

        // Attach persisted assignment/escalation state (one batched query), then
        // apply assignment-aware filters.
        $handoffs = $this->attachAssignments($handoffs, $user);
        $handoffs = array_values(array_filter($handoffs, fn (JourneyHandoff $h) => $this->passesAssignmentFilters($h, $filters, $user)));

        usort($handoffs, fn (JourneyHandoff $a, JourneyHandoff $b) => $b->rank() <=> $a->rank());

        return $handoffs;
    }

    /** Handoffs currently assigned to the given user (the "Assigned To Me" tab). */
    public function assignedToMe(User $user, array $filters = []): array
    {
        $assignments = JourneyHandoffAssignment::query()
            ->where('assigned_to_user_id', $user->id)
            ->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->with('assignedTo')
            ->latest('assigned_at')
            ->limit(self::MAX_ROWS)
            ->get();
        if ($assignments->isEmpty()) {
            return [];
        }

        $visits = Visit::with(['patient', 'currentDepartment', 'statusLogs', 'labRequests', 'prescriptions', 'admission'])
            ->whereIn('id', $assignments->pluck('visit_id')->unique())
            ->get()
            ->keyBy('id');

        $handoffs = [];
        foreach ($assignments as $assignment) {
            $visit = $visits->get($assignment->visit_id);
            if ($visit === null) {
                continue;
            }
            $handoff = $this->handoffs->resolve($visit, $user);
            // Only show if the derived handoff still matches this assignment identity.
            $key = JourneyHandoffAssignment::buildIdentityKey(
                $handoff->visitId, $handoff->cause, $handoff->fromDepartmentId, $handoff->toDepartmentId, $handoff->toDepartmentType
            );
            if ($key !== $assignment->identityKey()) {
                continue;
            }
            $handoff->attachAssignment($assignment, $this->escalation->levelFor($handoff, $assignment));
            if ($this->passesFilters($handoff, $filters) && $this->passesAssignmentFilters($handoff, $filters, $user)) {
                $handoffs[] = $handoff;
            }
        }

        usort($handoffs, fn (JourneyHandoff $a, JourneyHandoff $b) => $b->rank() <=> $a->rank());

        return $handoffs;
    }

    /**
     * Batch-attach persisted assignment + derived escalation to resolved handoffs.
     *
     * @param  list<JourneyHandoff>  $handoffs
     * @return list<JourneyHandoff>
     */
    private function attachAssignments(array $handoffs, ?User $user): array
    {
        if ($handoffs === []) {
            return $handoffs;
        }

        // One batched query covering active AND resolved rows: active ones attach;
        // a resolved/dismissed row for the same identity hides the handoff (it has
        // been operationally handled and should leave the active worklist).
        $assignments = JourneyHandoffAssignment::query()
            ->whereIn('visit_id', array_unique(array_map(fn (JourneyHandoff $h) => $h->visitId, $handoffs)))
            ->with('assignedTo')
            ->get();
        $activeByKey = $assignments->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->keyBy(fn (JourneyHandoffAssignment $a) => $a->identityKey());
        $resolvedKeys = $assignments->whereNotIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->map(fn (JourneyHandoffAssignment $a) => $a->identityKey())
            ->flip();

        $result = [];
        foreach ($handoffs as $handoff) {
            $key = JourneyHandoffAssignment::buildIdentityKey(
                $handoff->visitId, $handoff->cause, $handoff->fromDepartmentId, $handoff->toDepartmentId, $handoff->toDepartmentType
            );
            $assignment = $activeByKey->get($key);
            if ($assignment === null && $resolvedKeys->has($key)) {
                continue; // already handled — drop from the active worklist
            }
            $handoff->attachAssignment($assignment, $this->escalation->levelFor($handoff, $assignment));
            $result[] = $handoff;
        }

        return $result;
    }

    private function passesAssignmentFilters(JourneyHandoff $handoff, array $filters, ?User $user): bool
    {
        if (! empty($filters['assignment_status']) && $handoff->assignmentStatus !== $filters['assignment_status']) {
            return false;
        }
        if (! empty($filters['escalation_level']) && $handoff->escalationLevel !== $filters['escalation_level']) {
            return false;
        }
        if (! empty($filters['unassigned_only']) && ! $handoff->isUnassigned()) {
            return false;
        }
        if (! empty($filters['mine_only']) && $handoff->assignedToUserId !== $user?->id) {
            return false;
        }
        if (! empty($filters['assigned_to']) && $handoff->assignedToUserId !== (int) $filters['assigned_to']) {
            return false;
        }

        return true;
    }

    /**
     * Light (query-free) handoff rows for summaries/matrix: every active candidate
     * that is near-breach or worse, with just the fields aggregation needs.
     *
     * @return list<array{from_type:?string,to_domain:?string,elapsed:int,breached:bool}>
     */
    private function lightHandoffs(?int $scopeFromDeptId): array
    {
        $rows = [];
        foreach ($this->candidateRows($scopeFromDeptId) as $row) {
            $cause = $this->causes->quickCause(VisitStatus::tryFrom((string) $row->status), $row->from_dept_type);
            $domain = $cause->ownerType();
            if ($cause === JourneyDelayCause::UNKNOWN || $domain === null) {
                continue;
            }
            $elapsed = $this->elapsed($row->entered_at);
            $sla = $this->sla->evaluate($cause, $elapsed);
            if ($this->sla->rank($sla['sla_status']) < 2) {
                continue;
            }
            $rows[] = [
                'from_dept_id' => (int) $row->from_dept_id,
                'from_type' => $row->from_dept_type,
                'to_domain' => $domain,
                'elapsed' => $elapsed,
                'breached' => $this->sla->isBreached($sla['sla_status']),
                'sla_status' => $sla['sla_status'],
            ];
        }

        return $rows;
    }

    private function candidateRows(?int $fromDepartmentId): Collection
    {
        if (! DepartmentSchemaCache::hasTable('visits')) {
            return collect();
        }

        $column = DepartmentSchemaCache::hasColumn('visits', 'current_department_id') ? 'current_department_id' : 'department_id';

        try {
            $query = DB::table('visits')
                ->join('departments', 'departments.id', '=', 'visits.'.$column)
                ->whereIn('visits.status', config('journey.active_statuses', []))
                ->whereNull('visits.deleted_at')
                ->select([
                    'visits.id as visit_id',
                    'visits.'.$column.' as from_dept_id',
                    'departments.type as from_dept_type',
                    'visits.status as status',
                    'visits.updated_at as entered_at',
                ])
                ->orderByDesc('visits.updated_at')
                ->limit(self::CANDIDATE_CAP);

            if ($fromDepartmentId !== null) {
                $query->where('visits.'.$column, $fromDepartmentId);
            }

            return $query->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function passesFilters(JourneyHandoff $handoff, array $filters): bool
    {
        if (! empty($filters['severity']) && $handoff->severity !== $filters['severity']) {
            return false;
        }
        if (! empty($filters['sla_status']) && $handoff->slaStatus !== $filters['sla_status']) {
            return false;
        }
        if (! empty($filters['breached_only']) && ! $handoff->isBreached()) {
            return false;
        }
        if (! empty($filters['cause']) && $handoff->cause !== $filters['cause']) {
            return false;
        }
        if (! empty($filters['status']) && $handoff->actionStatus !== $filters['status']) {
            return false;
        }

        return true;
    }

    private function elapsed(mixed $enteredAt): int
    {
        return $enteredAt ? (int) round(Carbon::parse($enteredAt)->diffInMinutes(now())) : 0;
    }

    private function userSeesHandoff(User $user, ?string $fromType, ?string $toDomain): bool
    {
        $fromCap = $this->capabilityForType($fromType);
        if ($fromCap !== null && $this->capabilities->can($user, $fromCap)) {
            return true;
        }
        $toCap = $this->capabilityForType($this->ownerTypeToDepartmentType($toDomain));

        return $toCap !== null && $this->capabilities->can($user, $toCap);
    }

    private function canSeeType(User $user, ?string $type): bool
    {
        $capability = $this->capabilityForType($type);

        return $capability === null || $this->capabilities->can($user, $capability);
    }

    /** Owner domain (JourneyDelayCause::ownerType vocabulary) for a department type. */
    private function ownerDomainForType(?string $type): ?string
    {
        return match ($type) {
            'consultation', 'treatment', 'nursing', 'emergency', 'ambulance', 'records' => 'consultation',
            'procedure', 'theatre' => 'theatre',
            'investigation', 'blood_bank' => 'investigation',
            'radiology' => 'radiology',
            'pharmacy' => 'pharmacy',
            'inpatient', 'maternity' => 'ward',
            'finance', 'administrative' => 'finance',
            default => null,
        };
    }

    private function ownerTypeToDepartmentType(?string $ownerType): ?string
    {
        return match ($ownerType) {
            'consultation' => 'consultation',
            'investigation' => 'investigation',
            'radiology' => 'radiology',
            'pharmacy' => 'pharmacy',
            'ward' => 'inpatient',
            'finance' => 'finance',
            'theatre' => 'theatre',
            default => $ownerType,
        };
    }

    private function capabilityForType(?string $type): ?string
    {
        return match ($type) {
            'consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records' => 'consultation_access',
            'investigation', 'radiology', 'blood_bank' => 'investigation_access',
            'pharmacy' => 'pharmacy_access',
            'stores' => 'stock_access',
            'inpatient', 'maternity' => 'ward_access',
            'finance', 'administrative' => 'financial_access',
            default => null,
        };
    }

    private function typeValue(mixed $type): ?string
    {
        return $type instanceof \App\Enums\DepartmentType ? $type->value : (is_string($type) ? $type : null);
    }
}
