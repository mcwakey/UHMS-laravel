<?php

namespace App\Services\Dashboards;

use App\Enums\AdmissionStatus;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\MedicationAdministrationSchedule;
use App\Models\NursingNote;
use App\Models\Vital;
use App\Models\Ward;
use App\Services\Dashboards\Concerns\BuildsPressure;
use Illuminate\Support\Carbon;

/**
 * Metrics for the modern Nurse dashboard. Read-only aggregate queries.
 */
class NurseDashboardService
{
    use BuildsPressure;

    /** Vital thresholds used ONLY for display grouping — not clinical decisions. */
    private const CRITICAL = ['spo2_lt' => 90, 'hr_gt' => 130, 'hr_lt' => 45, 'temp_gte' => 39.5];
    private const MONITOR = ['spo2_lt' => 94, 'hr_gt' => 110, 'hr_lt' => 55, 'temp_gte' => 38.0];

    /** @return array<string, mixed> */
    public function build(): array
    {
        return [
            'insight' => $this->criticalInsight(),
            'pressure' => $this->medicationPressure(),
            'kpis' => $this->kpis(),
            'vitalsTrend' => $this->vitalsTrend(),
            'wardOccupancy' => $this->wardOccupancy(),
            'liveVitals' => $this->liveVitals(),
            'medicationSchedule' => MedicationAdministrationSchedule::with([
                'patient:id,patient_number,first_name,last_name,other_names',
                'medicationOrder:id,drug_name',
            ])
                ->whereIn('status', [
                    MedicationAdministrationSchedule::STATUS_SCHEDULED,
                    MedicationAdministrationSchedule::STATUS_DUE,
                ])
                ->whereBetween('scheduled_at', [now()->subHours(1), now()->addHours(6)])
                ->orderBy('scheduled_at')
                ->take(4)
                ->get(),
            'weeklyActivity' => $this->weeklyActivity(),
            'handoverNotes' => NursingNote::with('nurse:id,name')
                ->latest('observed_at')
                ->take(3)
                ->get(),
        ];
    }

    /**
     * Clinical safety banner: abnormal vitals today + overdue medications.
     *
     * @return array<string, mixed>|null
     */
    private function criticalInsight(): ?array
    {
        $critical = $this->criticalQuery()->count();
        $overdueMeds = MedicationAdministrationSchedule::whereIn('status', [
            MedicationAdministrationSchedule::STATUS_SCHEDULED,
            MedicationAdministrationSchedule::STATUS_DUE,
        ])->where('scheduled_at', '<', now())->whereDate('scheduled_at', today())->count();

        if ($critical < 1 && $overdueMeds < 1) {
            return null;
        }

        $leadsWithVitals = $critical >= $overdueMeds;

        return [
            'variant' => $critical > 0 ? 'danger' : 'warning',
            'icon' => 'ti-heartbeat',
            'title' => __('role_dashboards.nurse.clinical_insight'),
            'badge' => $leadsWithVitals
                ? __('role_dashboards.nurse.critical_vitals_count', ['count' => $critical])
                : __('role_dashboards.nurse.overdue_meds_count', ['count' => $overdueMeds]),
            'cause' => [
                'icon' => $leadsWithVitals ? 'ti-device-heart-monitor' : 'ti-pill',
                'label' => $leadsWithVitals ? __('role_dashboards.nurse.cause_abnormal_vitals') : __('role_dashboards.nurse.cause_overdue_meds'),
            ],
            'action' => $leadsWithVitals ? __('role_dashboards.nurse.action_review_vitals') : __('role_dashboards.nurse.action_administer_meds'),
            'link' => ['url' => route('admin.admissions.index'), 'label' => __('role_dashboards.nurse.view_admissions')],
        ];
    }

    /** @return array<string, mixed> */
    private function medicationPressure(): array
    {
        $pending = MedicationAdministrationSchedule::whereIn('status', [
            MedicationAdministrationSchedule::STATUS_SCHEDULED,
            MedicationAdministrationSchedule::STATUS_DUE,
        ]);
        $dueSoon = (clone $pending)->whereBetween('scheduled_at', [now()->subHours(1), now()->addHours(2)])->count();
        $overdue = (clone $pending)->where('scheduled_at', '<', now()->subHours(1))->whereDate('scheduled_at', today())->count();
        $givenToday = MedicationAdministrationSchedule::whereDate('scheduled_at', today())
            ->whereNotIn('status', [
                MedicationAdministrationSchedule::STATUS_SCHEDULED,
                MedicationAdministrationSchedule::STATUS_DUE,
            ])->count();

        return $this->pressure(
            __('role_dashboards.nurse.medication_load'),
            'ti-pill',
            $dueSoon + $overdue,
            [5, 12, 20],
            [
                __('role_dashboards.nurse.doses_due_soon', ['count' => $dueSoon]),
                __('role_dashboards.nurse.doses_overdue', ['count' => $overdue]),
                __('role_dashboards.nurse.doses_given_today', ['count' => $givenToday]),
            ],
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function kpis(): array
    {
        $vitalsToday = Vital::whereDate('recorded_at', today())->count();
        $vitalsYesterday = Vital::whereDate('recorded_at', today()->subDay())->count();

        return [
            'under_care' => [
                'value' => Admission::whereIn('status', [AdmissionStatus::ADMITTED->value, AdmissionStatus::ON_LEAVE->value])->count(),
                'trend' => null,
            ],
            'critical' => ['value' => $this->criticalQuery()->count(), 'trend' => null],
            'meds_due' => [
                'value' => MedicationAdministrationSchedule::whereIn('status', [
                    MedicationAdministrationSchedule::STATUS_SCHEDULED,
                    MedicationAdministrationSchedule::STATUS_DUE,
                ])->whereBetween('scheduled_at', [now()->subHours(1), now()->addHours(2)])->count(),
                'trend' => null,
            ],
            'vitals_today' => [
                'value' => $vitalsToday,
                'trend' => $vitalsYesterday > 0 ? (int) round((($vitalsToday - $vitalsYesterday) / $vitalsYesterday) * 100) : null,
            ],
        ];
    }

    private function criticalQuery()
    {
        $c = self::CRITICAL;

        return Vital::whereDate('recorded_at', today())->where(function ($q) use ($c) {
            $q->where('spo2', '<', $c['spo2_lt'])
                ->orWhere('heart_rate', '>', $c['hr_gt'])
                ->orWhere('heart_rate', '<', $c['hr_lt'])
                ->orWhere('temperature', '>=', $c['temp_gte']);
        });
    }

    /** Avg heart rate + SpO2 per 2-hour bucket today. @return array<string, mixed> */
    private function vitalsTrend(): array
    {
        $vitals = Vital::whereDate('recorded_at', today())
            ->whereNotNull('recorded_at')
            ->get(['recorded_at', 'heart_rate', 'spo2']);

        $labels = [];
        $hr = [];
        $spo2 = [];
        for ($h = 6; $h <= 20; $h += 2) {
            $labels[] = date('gA', mktime($h, 0));
            $bucket = $vitals->filter(function ($v) use ($h) {
                $hour = (int) Carbon::parse($v->recorded_at)->format('G');

                return $hour >= $h && $hour < $h + 2;
            });
            $hr[] = $bucket->avg('heart_rate') !== null ? round((float) $bucket->avg('heart_rate')) : null;
            $spo2[] = $bucket->avg('spo2') !== null ? round((float) $bucket->avg('spo2'), 1) : null;
        }

        return ['labels' => $labels, 'heart_rate' => $hr, 'spo2' => $spo2];
    }

    /** @return array{wards: array<int, array<string, mixed>>, average: int} */
    private function wardOccupancy(): array
    {
        $wards = Ward::where('is_active', true)
            ->withCount(['beds as occupied_count' => fn ($q) => $q->where('status', 'occupied')])
            ->withCount('beds')
            ->orderByDesc('capacity')
            ->take(3)
            ->get()
            ->map(function (Ward $ward) {
                $total = max(1, (int) ($ward->beds_count ?: $ward->capacity ?: 1));

                return [
                    'name' => $ward->name,
                    'pct' => (int) round(($ward->occupied_count / $total) * 100),
                ];
            })
            ->values()
            ->all();

        $totalBeds = Bed::count();
        $occupied = Bed::where('status', 'occupied')->count();

        return [
            'wards' => $wards,
            'average' => $totalBeds > 0 ? (int) round(($occupied / $totalBeds) * 100) : 0,
        ];
    }

    /** Latest vital per admitted patient with display status. @return array<int, array<string, mixed>> */
    private function liveVitals(): array
    {
        $admissions = Admission::with(['patient:id,patient_number,first_name,last_name,other_names', 'bed:id,bed_number,ward_id', 'bed.ward:id,name'])
            ->whereIn('status', [AdmissionStatus::ADMITTED->value, AdmissionStatus::ON_LEAVE->value])
            ->latest('admission_date')
            ->take(6)
            ->get();

        $rows = [];
        foreach ($admissions as $admission) {
            $vital = Vital::where('patient_id', $admission->patient_id)
                ->latest('recorded_at')
                ->first(['heart_rate', 'blood_pressure_systolic', 'blood_pressure_diastolic', 'spo2', 'temperature', 'recorded_at']);
            if ($vital === null) {
                continue;
            }
            $rows[] = [
                'patient' => $admission->patient,
                'bed' => $admission->bed?->bed_number,
                'ward' => $admission->bed?->ward?->name,
                'heart_rate' => $vital->heart_rate,
                'bp' => $vital->blood_pressure_systolic ? ($vital->blood_pressure_systolic.'/'.$vital->blood_pressure_diastolic) : null,
                'spo2' => $vital->spo2,
                'status' => $this->vitalStatus($vital),
            ];
            if (count($rows) >= 4) {
                break;
            }
        }

        return $rows;
    }

    private function vitalStatus($vital): string
    {
        $c = self::CRITICAL;
        $m = self::MONITOR;
        $hr = (float) ($vital->heart_rate ?? 0);
        $spo2 = (float) ($vital->spo2 ?? 100);
        $temp = (float) ($vital->temperature ?? 0);

        if (($vital->spo2 !== null && $spo2 < $c['spo2_lt']) || ($vital->heart_rate !== null && ($hr > $c['hr_gt'] || $hr < $c['hr_lt'])) || $temp >= $c['temp_gte']) {
            return 'critical';
        }
        if (($vital->spo2 !== null && $spo2 < $m['spo2_lt']) || ($vital->heart_rate !== null && ($hr > $m['hr_gt'] || $hr < $m['hr_lt'])) || ($temp >= $m['temp_gte'] && $temp > 0)) {
            return 'monitor';
        }

        return 'stable';
    }

    /** Vitals recorded per weekday x shift over the last 7 days (heatmap). @return array<int, array<string, mixed>> */
    private function weeklyActivity(): array
    {
        $vitals = Vital::where('recorded_at', '>=', now()->subDays(7)->startOfDay())
            ->whereNotNull('recorded_at')
            ->pluck('recorded_at');

        $shifts = ['night' => [22, 6], 'evening' => [17, 22], 'afternoon' => [12, 17], 'morning' => [6, 12]];
        $grid = [];
        foreach ($shifts as $shift => $_) {
            $grid[$shift] = array_fill(0, 7, 0);
        }

        $days = collect(range(6, 0))->map(fn ($i) => today()->subDays($i));
        $dayIndex = $days->mapWithKeys(fn ($d, $i) => [$d->toDateString() => $i])->all();

        foreach ($vitals as $ts) {
            $dt = Carbon::parse($ts);
            $idx = $dayIndex[$dt->toDateString()] ?? null;
            if ($idx === null) {
                continue;
            }
            $hour = (int) $dt->format('G');
            $shift = match (true) {
                $hour >= 6 && $hour < 12 => 'morning',
                $hour >= 12 && $hour < 17 => 'afternoon',
                $hour >= 17 && $hour < 22 => 'evening',
                default => 'night',
            };
            $grid[$shift][$idx]++;
        }

        $labels = $days->map(fn ($d) => $d->format('D'))->all();

        return [
            'labels' => $labels,
            'series' => collect($grid)->map(fn ($row, $shift) => [
                'name' => $shift,
                'data' => $row,
            ])->values()->all(),
        ];
    }
}
