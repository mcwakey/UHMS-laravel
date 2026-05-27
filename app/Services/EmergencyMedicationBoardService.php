<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ClinicalTask;
use App\Models\MedicationAdministrationSchedule;
use App\Models\MedicationOrder;

class EmergencyMedicationBoardService
{
    public function __construct(
        private ClinicalTaskReminderService $reminders,
        private MedicationProgressService $progress,
    ) {}

    public function board(): array
    {
        $this->reminders->syncMedicationTaskStatuses(
            ClinicalTask::query()->whereNull('admission_id')->whereNotNull('emergency_case_id')
        );

        $orders = MedicationOrder::query()
            ->with(['emergencyCase.bay', 'visit.patient', 'patient', 'frequency', 'prescriber', 'schedules.clinicalTask', 'schedules.administration.administeredBy'])
            ->whereNull('admission_id')
            ->where(function ($q) {
                $q->whereNotNull('emergency_case_id')
                    ->orWhereHas('visit', fn ($visit) => $visit
                        ->where('status', VisitStatus::EMERGENCY->value)
                        ->orWhere('visit_type', VisitType::EMERGENCY->value));
            })
            ->latest()
            ->get();

        $schedules = MedicationAdministrationSchedule::query()
            ->with(['emergencyCase.bay', 'medicationOrder.emergencyCase.bay', 'medicationOrder.visit.patient', 'medicationOrder.frequency', 'medicationOrder.prescriber', 'clinicalTask', 'administration.administeredBy'])
            ->whereNull('admission_id')
            ->where(function ($q) {
                $q->whereNotNull('emergency_case_id')
                    ->orWhereHas('medicationOrder.visit', fn ($visit) => $visit
                        ->where('status', VisitStatus::EMERGENCY->value)
                        ->orWhere('visit_type', VisitType::EMERGENCY->value));
            })
            ->whereDate('scheduled_at', '<=', today()->addDay())
            ->orderBy('scheduled_at')
            ->get();

        return [
            'orders' => $orders->map(fn (MedicationOrder $order) => [
                'order' => $order,
                'progress' => $this->progress->summarize($order),
            ]),
            'schedules' => $schedules,
            'counts' => $this->reminders->countsForEmergency(),
            'stat_due' => $schedules->filter(fn ($s) => $s->medicationOrder?->frequency?->is_stat && in_array($s->clinicalTask?->status, [ClinicalTask::STATUS_DUE, ClinicalTask::STATUS_OVERDUE], true))->values(),
            'due_now' => $schedules->filter(fn ($s) => $s->clinicalTask?->status === ClinicalTask::STATUS_DUE)->values(),
            'overdue' => $schedules->filter(fn ($s) => $s->clinicalTask?->status === ClinicalTask::STATUS_OVERDUE)->values(),
            'administered_today' => $schedules->filter(fn ($s) => $s->administration && $s->administration->administered_at?->isToday())->values(),
            'prn' => $orders->filter(fn ($order) => (bool) $order->frequency?->is_prn)->values(),
        ];
    }
}
