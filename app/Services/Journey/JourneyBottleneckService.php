<?php

namespace App\Services\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\User;
use App\Services\Department\DepartmentDashboardCapabilityService;
use App\Services\Department\DepartmentSchemaCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aggregates patient flow per department — waiting / delayed / average wait AND the
 * top delay cause — from a SINGLE portable query over active visits (bounded set,
 * aggregated in PHP). Causes are derived query-free via JourneyDelayCauseResolver's
 * quickCause(), so adding "why" costs no extra queries. No new storage.
 *
 * "Entered current stage" is approximated by `visits.updated_at`; precise per-patient
 * timing/cause lives in JourneyDelayService / JourneyDelayCauseResolver.
 */
class JourneyBottleneckService
{
    public function __construct(
        private DepartmentDashboardCapabilityService $capabilities,
        private JourneyDelayCauseResolver $causes,
    ) {}

    /**
     * @return list<array<string,mixed>>
     */
    public function bottlenecks(): array
    {
        return $this->aggregate($this->activeVisitRows());
    }

    /**
     * Bottlenecks the user is allowed to see (reuses dashboard capabilities), worst-first.
     *
     * @return list<array<string,mixed>>
     */
    public function forUser(User $user): array
    {
        $visible = array_filter(
            $this->bottlenecks(),
            fn (array $b) => $this->capabilities->can($user, $this->capabilityForType($b['department_type'])),
        );

        usort($visible, fn ($a, $b) => $b['delayed'] <=> $a['delayed'] ?: $b['waiting'] <=> $a['waiting']);

        return array_values($visible);
    }

    /** Bottleneck for a single department (dashboard insight) — one scoped query. */
    public function forDepartment(int $departmentId): ?array
    {
        return $this->aggregate($this->activeVisitRows($departmentId))[0] ?? null;
    }

    /**
     * Dashboard insight for a department: returns the scoped bottleneck only when the
     * type is journey-relevant AND the user holds the matching dashboard capability
     * (otherwise null — one query at most, none for non-flow departments).
     */
    public function insightForUser(User $user, ?int $departmentId, ?string $departmentType): ?array
    {
        if ($departmentId === null || $this->stageForType($departmentType) === null) {
            return null;
        }
        $capability = $this->capabilityForType($departmentType);
        if ($capability !== null && ! $this->capabilities->can($user, $capability)) {
            return null;
        }

        return $this->forDepartment($departmentId);
    }

    /**
     * Hospital-wide summary: the worst current bottleneck and the most common delay
     * cause across all delayed patients right now.
     *
     * @return array{worst:?array<string,mixed>,most_common_cause:?JourneyDelayCause,most_common_cause_count:int}
     */
    public function summary(): array
    {
        $bottlenecks = $this->bottlenecks();
        $worst = collect($bottlenecks)->sortByDesc('delayed')->first();

        $totals = [];
        foreach ($bottlenecks as $bottleneck) {
            foreach ($bottleneck['causes'] as $cause => $count) {
                $totals[$cause] = ($totals[$cause] ?? 0) + $count;
            }
        }
        arsort($totals);
        $topCause = array_key_first($totals);

        return [
            'worst' => $worst && $worst['delayed'] > 0 ? $worst : null,
            'most_common_cause' => $topCause ? JourneyDelayCause::from($topCause) : null,
            'most_common_cause_count' => $topCause ? $totals[$topCause] : 0,
        ];
    }

    private function activeVisitRows(?int $departmentId = null): Collection
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
                    'visits.'.$column.' as department_id',
                    'departments.name as department_name',
                    'departments.type as department_type',
                    'visits.status as status',
                    'visits.updated_at as entered_at',
                ]);

            if ($departmentId !== null) {
                $query->where('visits.'.$column, $departmentId);
            }

            return $query->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function aggregate(Collection $rows): array
    {
        $now = now();
        $grouped = [];

        foreach ($rows as $row) {
            $id = (int) $row->department_id;
            $grouped[$id] ??= [
                'department_id' => $id,
                'department_name' => $row->department_name,
                'department_type' => $row->department_type,
                'waiting' => 0,
                'delayed' => 0,
                'total_minutes' => 0,
                'causes' => [],
            ];

            $minutes = $row->entered_at ? (int) round(Carbon::parse($row->entered_at)->diffInMinutes($now)) : 0;
            $grouped[$id]['waiting']++;
            $grouped[$id]['total_minutes'] += $minutes;

            if ($minutes >= $this->thresholdFor($row->department_type)) {
                $grouped[$id]['delayed']++;
                $cause = $this->causes->quickCause(VisitStatus::tryFrom((string) $row->status), $row->department_type);
                $grouped[$id]['causes'][$cause->value] = ($grouped[$id]['causes'][$cause->value] ?? 0) + 1;
            }
        }

        return array_values(array_map(function (array $bottleneck) {
            $avg = $bottleneck['waiting'] > 0 ? (int) round($bottleneck['total_minutes'] / $bottleneck['waiting']) : 0;
            arsort($bottleneck['causes']);
            $topCauseKey = array_key_first($bottleneck['causes']);
            unset($bottleneck['total_minutes']);

            return $bottleneck + [
                'avg_wait_minutes' => $avg,
                'severity' => $bottleneck['delayed'] >= 5 ? 'critical' : ($bottleneck['delayed'] > 0 ? 'warning' : 'normal'),
                'top_cause' => $topCauseKey ? JourneyDelayCause::from($topCauseKey) : null,
            ];
        }, $grouped));
    }

    private function thresholdFor(?string $departmentType): int
    {
        $stage = $this->stageForType($departmentType);
        $config = $stage
            ? config('journey.thresholds.'.$stage->value, config('journey.default_threshold'))
            : config('journey.default_threshold');

        return (int) ($config['delayed'] ?? 90);
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
}
