<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\ClinicalTask;
use App\Models\MedicationAdministration;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use App\Models\Setting;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MarChartService
{
    private const FINAL_SCHEDULE_STATUSES = [
        MedicationAdministrationSchedule::STATUS_GIVEN,
        MedicationAdministrationSchedule::STATUS_PARTIALLY_GIVEN,
        MedicationAdministrationSchedule::STATUS_MISSED,
        MedicationAdministrationSchedule::STATUS_SKIPPED,
        MedicationAdministrationSchedule::STATUS_REFUSED,
        MedicationAdministrationSchedule::STATUS_HELD,
        MedicationAdministrationSchedule::STATUS_CANCELLED,
        MedicationAdministrationSchedule::STATUS_VOIDED,
        MedicationAdministrationSchedule::STATUS_CORRECTED,
    ];

    public function __construct(private ClinicalTaskReminderService $reminders) {}

    public function buildForAdmission(Admission $admission, Carbon $date): array
    {
        $admission->loadMissing([
            'patient.primaryInsurance.insuranceProvider',
            'visit.patient.primaryInsurance.insuranceProvider',
            'visit.visitInsurance.insuranceProvider',
            'visit.currentDepartment',
            'visit.activeConsultationRoute.doctor',
            'visit.pendingConsultationRoutes.doctor',
            'visit.consultationRoutes.doctor',
            'bed.ward.department',
            'admittedBy',
        ]);

        $this->reminders->syncMedicationTaskStatuses(
            ClinicalTask::query()->where('admission_id', $admission->id)
        );

        return $this->buildForEncounter($admission->visit, $date, $admission, 'admission');
    }

    public function buildForEmergencyVisit(Visit $visit, Carbon $date): array
    {
        $visit->loadMissing([
            'patient.primaryInsurance.insuranceProvider',
            'visitInsurance.insuranceProvider',
            'currentDepartment',
            'activeConsultationRoute.doctor',
            'pendingConsultationRoutes.doctor',
            'consultationRoutes.doctor',
        ]);

        $this->reminders->syncMedicationTaskStatuses(
            ClinicalTask::query()->where('visit_id', $visit->id)->whereNull('admission_id')
        );

        return $this->buildForEncounter($visit, $date, null, 'emergency');
    }

    public function buildForPatientVisit(Visit $visit, Carbon $date): array
    {
        $visit->loadMissing([
            'patient.primaryInsurance.insuranceProvider',
            'visitInsurance.insuranceProvider',
            'admission.bed.ward.department',
            'currentDepartment',
            'activeConsultationRoute.doctor',
            'pendingConsultationRoutes.doctor',
            'consultationRoutes.doctor',
        ]);

        $scope = ClinicalTask::query()->where(function ($query) use ($visit) {
            $query->where('visit_id', $visit->id);

            if ($visit->admission) {
                $query->orWhere('admission_id', $visit->admission->id);
            }
        });

        $this->reminders->syncMedicationTaskStatuses($scope);

        return $this->buildForEncounter($visit, $date, $visit->admission, 'visit');
    }

    private function buildForEncounter(Visit $visit, Carbon $date, ?Admission $admission, string $context): array
    {
        $selectedDate = $date->copy()->startOfDay();
        $dayStart = $selectedDate->copy()->startOfDay();
        $dayEnd = $selectedDate->copy()->endOfDay();

        $orders = $this->queryOrders($visit, $admission, $dayStart, $dayEnd);
        $orderIds = $orders->pluck('id')->all();
        $dailyAdministrations = $this->dailyAdministrations($orderIds, $dayStart, $dayEnd);
        $timeColumns = $this->timeColumns($orders, $selectedDate);
        $progress = $this->progressForOrders($orders, $orderIds);

        $medicationRows = $orders
            ->reject(fn (MedicationOrder $order) => (bool) $order->frequency?->is_prn)
            ->map(fn (MedicationOrder $order) => $this->buildMedicationRow($order, $timeColumns, $progress[$order->id] ?? null))
            ->values();

        $prnMedications = $orders
            ->filter(fn (MedicationOrder $order) => (bool) $order->frequency?->is_prn)
            ->map(fn (MedicationOrder $order) => $this->buildPrnMedication($order, $dailyAdministrations, $progress[$order->id] ?? null))
            ->values();

        return [
            'context' => $context,
            'patient' => $visit->patient,
            'visit' => $visit,
            'admission' => $admission,
            'emergency_case' => null,
            'header' => $this->header($visit, $admission, $selectedDate),
            'selected_date' => $selectedDate,
            'previous_date' => $selectedDate->copy()->subDay(),
            'next_date' => $selectedDate->copy()->addDay(),
            'generated_at' => now(),
            'time_columns' => $timeColumns,
            'medication_rows' => $medicationRows,
            'prn_medications' => $prnMedications,
            'daily_administrations' => $this->normalizeDailyAdministrations($dailyAdministrations),
            'summary' => $this->summary($medicationRows, $dailyAdministrations),
            'legend' => $this->legend(),
        ];
    }

    private function queryOrders(Visit $visit, ?Admission $admission, Carbon $dayStart, Carbon $dayEnd): Collection
    {
        return MedicationOrder::query()
            ->with([
                'patient',
                'visit.patient',
                'admission.bed.ward.department',
                'product',
                'drug',
                'frequency',
                'prescriber',
                'schedules' => fn ($query) => $query
                    ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
                    ->with([
                        'clinicalTask',
                        'administration.administeredBy',
                        'administration.witness',
                        'administration.stockLocation',
                        'administration.stockMovement',
                    ])
                    ->orderBy('scheduled_at'),
            ])
            ->where(function ($query) use ($visit, $admission) {
                $query->where('visit_id', $visit->id);

                if ($admission) {
                    $query->orWhere('admission_id', $admission->id);
                }
            })
            ->orderBy('drug_name')
            ->orderBy('id')
            ->get();
    }

    private function dailyAdministrations(array $orderIds, Carbon $dayStart, Carbon $dayEnd): Collection
    {
        if (empty($orderIds)) {
            return collect();
        }

        return MedicationAdministration::query()
            ->whereIn('medication_order_id', $orderIds)
            ->whereBetween('administered_at', [$dayStart, $dayEnd])
            ->with([
                'medicationOrder.frequency',
                'schedule.clinicalTask',
                'administeredBy',
                'witness',
                'stockLocation',
                'stockMovement',
            ])
            ->orderBy('administered_at')
            ->get();
    }

    private function timeColumns(Collection $orders, Carbon $selectedDate): Collection
    {
        // Actual generated schedule times for the day (nurse-adjusted times included).
        $actual = $orders
            ->flatMap(fn (MedicationOrder $order) => $order->schedules)
            ->map(fn (MedicationAdministrationSchedule $schedule) => $schedule->scheduled_at?->format('H:i'))
            ->filter();

        // Planned dose times derived from each fixed-schedule order's frequency for the
        // selected day. This makes the grid show the day's dose slots even before the
        // schedule rows are generated (e.g. a freshly created order viewed on its start day).
        $planned = $orders
            ->reject(fn (MedicationOrder $order) => (bool) $order->frequency?->is_prn)
            ->flatMap(fn (MedicationOrder $order) => $this->plannedDoseTimesForDay($order, $selectedDate));

        return $actual
            ->merge($planned)
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Clock times (H:i) a fixed-schedule medication is due on the selected day,
     * derived from its frequency and order window. Returns [] when the order does
     * not fall on the day or is not a scheduled medication.
     */
    private function plannedDoseTimesForDay(MedicationOrder $order, Carbon $selectedDate): array
    {
        $frequency = $order->frequency;

        if (! $frequency || ! $frequency->requires_schedule || $frequency->is_prn) {
            return [];
        }

        $dayStart = $selectedDate->copy()->startOfDay();
        $dayEnd = $selectedDate->copy()->endOfDay();
        $start = $order->start_at?->copy();
        $end = $order->end_at?->copy();

        // Order's active window must overlap the selected day.
        if ($start && $start->greaterThan($dayEnd)) {
            return [];
        }
        if ($end && $end->lessThan($dayStart)) {
            return [];
        }

        if ($frequency->is_stat) {
            return $start && $start->greaterThanOrEqualTo($dayStart) && $start->lessThanOrEqualTo($dayEnd)
                ? [$start->format('H:i')]
                : [];
        }

        if ($frequency->interval_hours) {
            $times = [];
            $cursor = ($start ?? $dayStart)->copy();
            $guard = 0;
            while ($cursor->lessThan($dayStart) && $guard++ < 1000) {
                $cursor->addHours((int) $frequency->interval_hours);
            }
            while ($cursor->lessThanOrEqualTo($dayEnd) && (! $end || $cursor->lessThanOrEqualTo($end))) {
                $times[] = $cursor->format('H:i');
                $cursor->addHours((int) $frequency->interval_hours);
            }

            return array_values(array_unique($times));
        }

        $times = [];
        foreach (collect($frequency->default_times ?: ['08:00']) as $clock) {
            [$hour, $minute] = array_pad(explode(':', (string) $clock), 2, 0);
            $doseAt = $selectedDate->copy()->setTime((int) $hour, (int) $minute);

            if ($start && $doseAt->lessThan($start)) {
                continue;
            }
            if ($end && $doseAt->greaterThan($end)) {
                continue;
            }

            $times[] = $doseAt->format('H:i');
        }

        return $times;
    }

    private function buildMedicationRow(MedicationOrder $order, Collection $timeColumns, ?array $progress): array
    {
        $schedulesByTime = $order->schedules->groupBy(fn (MedicationAdministrationSchedule $schedule) => $schedule->scheduled_at?->format('H:i'));
        $cells = [];

        foreach ($timeColumns as $time) {
            $schedule = $schedulesByTime->get($time)?->first();
            $cells[$time] = $schedule ? $this->cell($schedule) : null;
        }

        return [
            'medication_order_id' => $order->id,
            'order' => $order,
            'product_name' => $order->display_name,
            'dose' => $order->dose,
            'dose_unit' => $order->dose_unit,
            'route' => $order->route,
            'frequency' => $order->frequency_code ?: $order->frequency?->code,
            'prescriber' => $order->prescriber?->name,
            'instructions' => $order->instructions,
            'start_at' => $order->start_at,
            'end_at' => $order->end_at,
            'status' => $order->status,
            'is_stat' => (bool) $order->frequency?->is_stat,
            'progress' => $progress ?? $this->emptyProgress($order),
            'cells' => $cells,
            'schedules' => $order->schedules,
        ];
    }

    private function cell(MedicationAdministrationSchedule $schedule): array
    {
        $administration = $schedule->administration;
        $status = $this->displayStatus($schedule);

        return [
            'time' => $schedule->scheduled_at?->format('H:i'),
            'schedule_id' => $schedule->id,
            'clinical_task_id' => $schedule->clinical_task_id,
            'status' => $status,
            'status_class' => $this->statusClass($status),
            'is_actionable' => ! $administration && in_array($status, [
                MedicationAdministrationSchedule::STATUS_DUE,
                MedicationAdministrationSchedule::STATUS_OVERDUE,
            ], true),
            'is_closed' => $administration || in_array($status, self::FINAL_SCHEDULE_STATUSES, true),
            'scheduled_at' => $schedule->scheduled_at,
            'dose' => $schedule->dose,
            'dose_unit' => $schedule->dose_unit,
            'route' => $schedule->route,
            'schedule' => $schedule,
            'administration' => $administration,
            'administered_by' => $administration?->administeredBy?->name,
            'administered_at' => $administration?->administered_at,
            'dose_given' => $administration?->dose_given,
            'reason_not_given' => $administration?->reason_not_given,
            'notes' => $administration?->notes,
            'reaction' => $administration?->reaction,
            'witness' => $administration?->witness?->name,
            'stock_source' => $administration?->source_stock_type,
            'stock_location' => $administration?->stockLocation?->name,
            'stock_movement_id' => $administration?->stock_movement_id,
            'corrected_at' => $administration?->corrected_at,
            'correction_reason' => $administration?->correction_reason,
        ];
    }

    private function displayStatus(MedicationAdministrationSchedule $schedule): string
    {
        if ($schedule->administration) {
            return $schedule->status === MedicationAdministrationSchedule::STATUS_MISSED
                && $schedule->administration->status === MedicationAdministration::STATUS_NOT_GIVEN
                    ? MedicationAdministrationSchedule::STATUS_MISSED
                    : $schedule->administration->status;
        }

        if (in_array($schedule->status, self::FINAL_SCHEDULE_STATUSES, true)) {
            return $schedule->status;
        }

        if (in_array($schedule->clinicalTask?->status, [
            ClinicalTask::STATUS_SCHEDULED,
            ClinicalTask::STATUS_DUE,
            ClinicalTask::STATUS_OVERDUE,
        ], true)) {
            return $schedule->clinicalTask->status;
        }

        $overdueAfter = (int) Setting::getValue('medication', 'medication_task_overdue_after_minutes', 15);
        $minutesLate = $schedule->scheduled_at?->diffInMinutes(now(), false) ?? null;

        if ($minutesLate === null || $minutesLate < 0) {
            return MedicationAdministrationSchedule::STATUS_SCHEDULED;
        }

        return $minutesLate >= $overdueAfter
            ? MedicationAdministrationSchedule::STATUS_OVERDUE
            : MedicationAdministrationSchedule::STATUS_DUE;
    }

    private function buildPrnMedication(MedicationOrder $order, Collection $dailyAdministrations, ?array $progress): array
    {
        $orderAdministrations = $dailyAdministrations
            ->where('medication_order_id', $order->id)
            ->values();

        return [
            'medication_order_id' => $order->id,
            'order' => $order,
            'product_name' => $order->display_name,
            'dose' => $order->dose,
            'dose_unit' => $order->dose_unit,
            'route' => $order->route,
            'frequency' => $order->frequency_code ?: $order->frequency?->code,
            'instructions' => $order->instructions,
            'prescriber' => $order->prescriber?->name,
            'last_administered_at' => $orderAdministrations->last()?->administered_at,
            'total_administrations_today' => $orderAdministrations->count(),
            'administrations' => $this->normalizeDailyAdministrations($orderAdministrations),
            'progress' => $progress ?? $this->emptyProgress($order),
        ];
    }

    private function progressForOrders(Collection $orders, array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $statusCounts = MedicationAdministrationSchedule::query()
            ->selectRaw('medication_order_id, status, COUNT(*) as aggregate')
            ->whereIn('medication_order_id', $orderIds)
            ->groupBy('medication_order_id', 'status')
            ->get()
            ->groupBy('medication_order_id');

        $nextDue = MedicationAdministrationSchedule::query()
            ->selectRaw('medication_order_id, MIN(scheduled_at) as next_due_at')
            ->whereIn('medication_order_id', $orderIds)
            ->whereNotIn('status', self::FINAL_SCHEDULE_STATUSES)
            ->groupBy('medication_order_id')
            ->pluck('next_due_at', 'medication_order_id');

        $patientStockUsed = MedicationAdministration::query()
            ->selectRaw('medication_order_id, COUNT(*) as aggregate')
            ->whereIn('medication_order_id', $orderIds)
            ->where('source_stock_type', MedicationAdministration::SOURCE_PATIENT_STOCK)
            ->whereIn('status', [
                MedicationAdministration::STATUS_GIVEN,
                MedicationAdministration::STATUS_PARTIALLY_GIVEN,
            ])
            ->groupBy('medication_order_id')
            ->pluck('aggregate', 'medication_order_id');

        $progress = [];

        foreach ($orders as $order) {
            $counts = $statusCounts->get($order->id, collect())
                ->pluck('aggregate', 'status')
                ->map(fn ($value) => (int) $value);

            $given = (int) ($counts[MedicationAdministrationSchedule::STATUS_GIVEN] ?? 0);
            $partial = (int) ($counts[MedicationAdministrationSchedule::STATUS_PARTIALLY_GIVEN] ?? 0);
            $missed = (int) ($counts[MedicationAdministrationSchedule::STATUS_MISSED] ?? 0);
            $held = (int) ($counts[MedicationAdministrationSchedule::STATUS_HELD] ?? 0);
            $refused = (int) ($counts[MedicationAdministrationSchedule::STATUS_REFUSED] ?? 0);
            $cancelled = (int) ($counts[MedicationAdministrationSchedule::STATUS_CANCELLED] ?? 0);
            $closed = collect(self::FINAL_SCHEDULE_STATUSES)->sum(fn ($status) => (int) ($counts[$status] ?? 0));
            $scheduledTotal = $counts->sum();
            $total = max((int) $order->total_doses, $scheduledTotal);
            $usedPatientDoses = (int) ($patientStockUsed[$order->id] ?? 0);

            $progress[$order->id] = [
                'total_doses' => $total,
                'given_doses' => $given,
                'partial_doses' => $partial,
                'remaining_doses' => max(0, $total - $closed),
                'missed_doses' => $missed,
                'held_doses' => $held,
                'refused_doses' => $refused,
                'cancelled_doses' => $cancelled,
                'closed_doses' => $closed,
                'next_due_at' => isset($nextDue[$order->id]) ? Carbon::parse($nextDue[$order->id]) : null,
                'progress_percentage' => $total > 0 ? round((($given + $partial) / $total) * 100, 1) : 0,
                'available_patient_doses' => max(0, (float) $order->quantity_dispensed - $usedPatientDoses),
                'is_prn' => (bool) $order->frequency?->is_prn,
                'is_stat' => (bool) $order->frequency?->is_stat,
            ];
        }

        return $progress;
    }

    private function emptyProgress(MedicationOrder $order): array
    {
        return [
            'total_doses' => (int) $order->total_doses,
            'given_doses' => 0,
            'partial_doses' => 0,
            'remaining_doses' => (int) $order->total_doses,
            'missed_doses' => 0,
            'held_doses' => 0,
            'refused_doses' => 0,
            'cancelled_doses' => 0,
            'closed_doses' => 0,
            'next_due_at' => null,
            'progress_percentage' => 0,
            'available_patient_doses' => (float) $order->quantity_dispensed,
            'is_prn' => (bool) $order->frequency?->is_prn,
            'is_stat' => (bool) $order->frequency?->is_stat,
        ];
    }

    private function normalizeDailyAdministrations(Collection $administrations): Collection
    {
        return $administrations->map(fn (MedicationAdministration $administration) => [
            'administration' => $administration,
            'medication' => $administration->medicationOrder?->display_name,
            'scheduled_at' => $administration->scheduled_at,
            'administered_at' => $administration->administered_at,
            'status' => $administration->status,
            'status_class' => $this->statusClass($administration->status),
            'administered_by' => $administration->administeredBy?->name,
            'dose_given' => $administration->dose_given,
            'route' => $administration->route,
            'reason_not_given' => $administration->reason_not_given,
            'notes' => $administration->notes,
            'reaction' => $administration->reaction,
            'witness' => $administration->witness?->name,
            'stock_source' => $administration->source_stock_type,
            'stock_location' => $administration->stockLocation?->name,
            'stock_movement_id' => $administration->stock_movement_id,
        ])->values();
    }

    private function summary(Collection $medicationRows, Collection $dailyAdministrations): array
    {
        $cells = $medicationRows->flatMap(fn (array $row) => collect($row['cells'])->filter());

        return [
            'due_now' => $cells->where('status', MedicationAdministrationSchedule::STATUS_DUE)->count(),
            'overdue' => $cells->where('status', MedicationAdministrationSchedule::STATUS_OVERDUE)->count(),
            'given_today' => $dailyAdministrations->where('status', MedicationAdministration::STATUS_GIVEN)->count(),
            'partial_today' => $dailyAdministrations->where('status', MedicationAdministration::STATUS_PARTIALLY_GIVEN)->count(),
            'missed_today' => $dailyAdministrations->whereIn('status', [
                MedicationAdministration::STATUS_MISSED,
                MedicationAdministration::STATUS_NOT_GIVEN,
            ])->count(),
            'held_today' => $dailyAdministrations->where('status', MedicationAdministration::STATUS_HELD)->count(),
            'refused_today' => $dailyAdministrations->where('status', MedicationAdministration::STATUS_REFUSED)->count(),
            'upcoming' => $cells->where('status', MedicationAdministrationSchedule::STATUS_SCHEDULED)->count(),
            'administered_today' => $dailyAdministrations->count(),
        ];
    }

    private function header(Visit $visit, ?Admission $admission, Carbon $selectedDate): array
    {
        $patient = $visit->patient;
        $ward = $admission?->bed?->ward;
        $doctor = $visit->currentConsultationDoctor();
        $insurance = $visit->visitInsurance?->insuranceProvider?->name
            ?? $patient?->primaryInsurance?->insuranceProvider?->name;

        return [
            'patient_name' => $patient?->full_name ?? 'Patient',
            'patient_number' => $patient?->patient_number,
            'age' => $visit->patient_age ?: ($patient?->date_of_birth?->age),
            'gender' => $this->enumLabel($patient?->gender),
            'visit_number' => $visit->visit_number,
            'admission_number' => $admission?->admission_number,
            'emergency_number' => ! $admission ? $visit->visit_number : null,
            'ward_bed' => $ward ? trim($ward->name.' / Bed '.($admission?->bed?->bed_number ?? '-')) : null,
            'location' => $visit->currentDepartment?->name,
            'visit_type' => $this->enumLabel($visit->visit_type),
            'current_status' => $this->enumLabel($admission?->status ?? $visit->status),
            'primary_doctor' => $doctor?->name,
            'insurance' => $insurance,
            'selected_date' => $selectedDate,
            'generated_at' => now(),
        ];
    }

    private function enumLabel($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'label')) {
            return $value->label();
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        return (string) $value;
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            MedicationAdministrationSchedule::STATUS_DUE => 'info',
            MedicationAdministrationSchedule::STATUS_OVERDUE => 'danger',
            MedicationAdministrationSchedule::STATUS_GIVEN => 'success',
            MedicationAdministrationSchedule::STATUS_PARTIALLY_GIVEN => 'success',
            MedicationAdministrationSchedule::STATUS_HELD => 'warning',
            MedicationAdministrationSchedule::STATUS_MISSED => 'danger',
            MedicationAdministrationSchedule::STATUS_REFUSED => 'warning',
            MedicationAdministrationSchedule::STATUS_SKIPPED => 'secondary',
            MedicationAdministrationSchedule::STATUS_CANCELLED => 'dark',
            MedicationAdministrationSchedule::STATUS_VOIDED => 'dark',
            MedicationAdministrationSchedule::STATUS_CORRECTED => 'primary',
            default => 'secondary',
        };
    }

    private function legend(): array
    {
        return [
            'GIVEN' => 'Dose administered',
            'DUE' => 'Dose due now',
            'OVERDUE' => 'Dose not administered after due time',
            'HELD' => 'Temporarily held',
            'MISSED' => 'Missed dose',
            'REFUSED' => 'Patient refused',
            'SKIPPED' => 'Intentionally skipped',
            'CANCELLED' => 'Cancelled future dose',
            'VOIDED' => 'Voided record',
            'CORRECTED' => 'Corrected with audit trail',
        ];
    }
}