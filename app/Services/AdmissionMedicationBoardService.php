<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Models\Admission;
use App\Models\ClinicalTask;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;
use Illuminate\Support\Collection;

class AdmissionMedicationBoardService
{
    public function __construct(
        private ClinicalTaskReminderService $reminders,
        private MedicationProgressService $progress,
        private InpatientWorkspaceScope $inpatientScope,
    ) {}

    public function index(array $filters = []): Collection
    {
        $this->reminders->syncMedicationTaskStatuses();

        $query = Admission::query()
            ->with(['patient', 'bed.ward', 'medicationOrders.frequency', 'medicationOrders.schedules'])
            ->where('status', AdmissionStatus::ADMITTED->value);
        $this->inpatientScope->admissions($query);

        return $query
            ->when($filters['ward_id'] ?? null, fn ($q, $wardId) => $q->whereHas('bed', fn ($b) => $b->where('ward_id', $wardId)))
            ->orderByDesc('admission_date')
            ->get()
            ->map(fn (Admission $admission) => $this->patientRow($admission));
    }

    public function forAdmission(Admission $admission): array
    {
        $this->reminders->countsForAdmission($admission->id);

        $admission->loadMissing([
            'patient',
            'bed.ward',
            'medicationOrders.frequency',
            'medicationOrders.prescriber',
            'medicationOrders.product',
            'medicationOrders.drug',
            'medicationOrders.schedules.clinicalTask',
            'medicationOrders.schedules.administration.administeredBy',
            'medicationOrders.administrations.administeredBy',
        ]);

        $orders = $admission->medicationOrders->map(function (MedicationOrder $order) {
            return [
                'order' => $order,
                'progress' => $this->progress->summarize($order),
            ];
        });

        $schedules = $admission->medicationSchedules()
            ->with(['medicationOrder.frequency', 'medicationOrder.prescriber', 'clinicalTask', 'administration.administeredBy'])
            ->whereDate('scheduled_at', '<=', today()->addDay())
            ->orderBy('scheduled_at')
            ->get();

        return [
            'admission' => $admission,
            'orders' => $orders,
            'schedules' => $schedules,
            'counts' => $this->reminders->countsForAdmission($admission->id),
            'due_now' => $this->filterByTaskStatus($schedules, [ClinicalTask::STATUS_DUE]),
            'overdue' => $this->filterByTaskStatus($schedules, [ClinicalTask::STATUS_OVERDUE]),
            'upcoming' => $schedules->filter(fn ($s) => $s->clinicalTask?->status === ClinicalTask::STATUS_SCHEDULED && $s->scheduled_at->between(now(), now()->addMinutes(30)))->values(),
            'completed' => $schedules->filter(fn ($s) => $s->clinicalTask?->status === ClinicalTask::STATUS_COMPLETED || $s->status === MedicationAdministrationSchedule::STATUS_GIVEN)->values(),
            'prn' => $admission->medicationOrders->filter(fn ($order) => (bool) $order->frequency?->is_prn)->values(),
        ];
    }

    private function patientRow(Admission $admission): array
    {
        $counts = $this->reminders->countsForAdmission($admission->id);

        return [
            'admission' => $admission,
            'active_medication_count' => $admission->medicationOrders
                ->whereNotIn('status', [
                    MedicationOrder::STATUS_COMPLETED,
                    MedicationOrder::STATUS_STOPPED,
                    MedicationOrder::STATUS_CANCELLED,
                ])
                ->count(),
            'counts' => $counts,
        ];
    }

    private function filterByTaskStatus(Collection $schedules, array $statuses): Collection
    {
        return $schedules->filter(fn ($schedule) => in_array($schedule->clinicalTask?->status, $statuses, true))->values();
    }
}
