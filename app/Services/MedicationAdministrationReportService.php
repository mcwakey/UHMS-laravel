<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\MedicationAdministration;
use Illuminate\Pagination\LengthAwarePaginator;

class MedicationAdministrationReportService
{
    public function administrations(array $filters = []): LengthAwarePaginator
    {
        return MedicationAdministration::query()
            ->with(['patient', 'admission.bed.ward', 'medicationOrder.prescriber', 'medicationOrder.frequency', 'administeredBy'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('administered_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('administered_at', '<=', $date))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', strtoupper($status)))
            ->when($filters['nurse_id'] ?? null, fn ($q, $nurseId) => $q->where('administered_by', $nurseId))
            ->when($filters['patient_id'] ?? null, fn ($q, $patientId) => $q->where('patient_id', $patientId))
            ->latest('administered_at')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }

    public function overdueTasks(array $filters = []): LengthAwarePaginator
    {
        return ClinicalTask::query()
            ->with(['patient', 'admission.bed.ward', 'schedule.medicationOrder.prescriber'])
            ->where('task_type', ClinicalTask::TYPE_MEDICATION_ADMINISTRATION)
            ->where('status', ClinicalTask::STATUS_OVERDUE)
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('due_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('due_at', '<=', $date))
            ->orderBy('due_at')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }
}
