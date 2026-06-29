<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyAction;
use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
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
 * Builds department/user journey worklists: the delayed patients that need action,
 * owned by departments the user may see, worst-first. Bounded: one candidate query,
 * then the full action resolver runs only on the (eager-loaded) displayed rows.
 */
class JourneyWorklistService
{
    /** Max rows resolved/displayed. */
    public const MAX_ROWS = 50;

    /** Safety cap on the candidate scan. */
    private const CANDIDATE_CAP = 500;

    public function __construct(
        private DepartmentDashboardCapabilityService $capabilities,
        private JourneyActionResolver $actions,
        private JourneyDelayCauseResolver $causes,
    ) {}

    /** @return list<JourneyAction> */
    public function forUser(User $user, array $filters = []): array
    {
        return $this->build($user, $filters['department_id'] ?? null, $filters);
    }

    /** @return list<JourneyAction> */
    public function forDepartment(Department $department, User $user, array $filters = []): array
    {
        $capability = $this->capabilityForType($this->typeValue($department->type));
        if ($capability !== null && ! $this->capabilities->can($user, $capability)) {
            return [];
        }

        return $this->build($user, $department->id, $filters);
    }

    /**
     * Light summary (counts + top cause) for menus/badges — uses the bounded
     * candidate query + query-free quickCause, NOT the full per-row resolver.
     *
     * @return array{total:int,critical:int,delayed:int,top_cause:?JourneyDelayCause}
     */
    public function summaryForUser(User $user): array
    {
        $total = 0;
        $critical = 0;
        $delayed = 0;
        $byCause = [];

        foreach ($this->candidateRows(null) as $row) {
            $type = $row->department_type;
            $capability = $this->capabilityForType($type);
            if ($capability !== null && ! $this->capabilities->can($user, $capability)) {
                continue;
            }

            $minutes = $row->entered_at ? (int) round(Carbon::parse($row->entered_at)->diffInMinutes(now())) : 0;
            $thresholds = $this->thresholdsFor($type);
            if ($minutes < $thresholds['delayed']) {
                continue;
            }

            $total++;
            $minutes >= $thresholds['critical'] ? $critical++ : $delayed++;
            $cause = $this->causes->quickCause(\App\Enums\VisitStatus::tryFrom((string) $row->status), $type);
            $byCause[$cause->value] = ($byCause[$cause->value] ?? 0) + 1;
        }

        arsort($byCause);
        $topCause = array_key_first($byCause);

        return [
            'total' => $total,
            'critical' => $critical,
            'delayed' => $delayed,
            'top_cause' => $topCause ? JourneyDelayCause::from($topCause) : null,
        ];
    }

    /**
     * @return list<JourneyAction>
     */
    private function build(User $user, ?int $departmentId, array $filters): array
    {
        // 1. Candidate active visits (one bounded query), filtered to the user's
        //    capabilities + proxy-delayed (updated_at older than the type threshold).
        $candidates = [];
        foreach ($this->candidateRows($departmentId) as $row) {
            $type = $row->department_type;
            $capability = $this->capabilityForType($type);
            if ($capability !== null && ! $this->capabilities->can($user, $capability)) {
                continue;
            }
            $minutes = $row->entered_at ? (int) round(Carbon::parse($row->entered_at)->diffInMinutes(now())) : 0;
            if ($minutes < $this->thresholdFor($type)) {
                continue;
            }
            $candidates[] = ['id' => (int) $row->visit_id, 'minutes' => $minutes];
        }

        usort($candidates, fn ($a, $b) => $b['minutes'] <=> $a['minutes']);
        $ids = array_slice(array_column($candidates, 'id'), 0, self::MAX_ROWS);
        if ($ids === []) {
            return [];
        }

        // 2. Eager-load relations once so the resolver does no per-row relation queries.
        $visits = Visit::with(['patient', 'currentDepartment', 'statusLogs', 'labRequests', 'prescriptions', 'admission'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        // 3. Resolve full actions for displayed rows; keep delayed/critical; filter; sort.
        $actions = [];
        foreach ($ids as $id) {
            $visit = $visits->get($id);
            if ($visit === null) {
                continue;
            }
            $action = $this->actions->resolve($visit, $user);
            if (! in_array($action->severity, ['delayed', 'critical'], true)) {
                continue;
            }
            if (! $this->passesFilters($action, $filters)) {
                continue;
            }
            $actions[] = $action;
        }

        usort($actions, fn (JourneyAction $a, JourneyAction $b) => $b->rank() <=> $a->rank());

        return $actions;
    }

    private function candidateRows(?int $departmentId): Collection
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
                    'departments.type as department_type',
                    'visits.status as status',
                    'visits.updated_at as entered_at',
                ])
                ->orderByDesc('visits.updated_at')
                ->limit(self::CANDIDATE_CAP);

            if ($departmentId !== null) {
                $query->where('visits.'.$column, $departmentId);
            }

            return $query->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function passesFilters(JourneyAction $action, array $filters): bool
    {
        if (! empty($filters['severity']) && $action->severity !== $filters['severity']) {
            return false;
        }
        if (! empty($filters['cause']) && $action->cause !== $filters['cause']) {
            return false;
        }
        if (! empty($filters['stage']) && $action->stage !== $filters['stage']) {
            return false;
        }
        if (! empty($filters['status']) && $action->actionStatus !== $filters['status']) {
            return false;
        }

        return true;
    }

    private function thresholdFor(?string $departmentType): int
    {
        return $this->thresholdsFor($departmentType)['delayed'];
    }

    /** @return array{delayed:int,critical:int} */
    private function thresholdsFor(?string $departmentType): array
    {
        $stage = $this->stageForType($departmentType);
        $config = $stage
            ? config('journey.thresholds.'.$stage->value, config('journey.default_threshold'))
            : config('journey.default_threshold');

        return [
            'delayed' => (int) ($config['delayed'] ?? 90),
            'critical' => (int) ($config['critical'] ?? 180),
        ];
    }

    private function stageForType(?string $type): ?PatientJourneyStage
    {
        return match ($type) {
            'consultation', 'treatment', 'records', 'nursing', 'emergency', 'ambulance' => PatientJourneyStage::CONSULTATION,
            'procedure', 'theatre' => PatientJourneyStage::PROCEDURE,
            'investigation', 'radiology', 'blood_bank' => PatientJourneyStage::INVESTIGATION,
            'pharmacy' => PatientJourneyStage::PHARMACY,
            'inpatient', 'maternity' => PatientJourneyStage::ADMISSION,
            default => null,
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
