<?php

namespace App\Services;

use App\Models\ClinicalTask;
use App\Models\EmergencyCase;

class EmergencyBoardService
{
    public function board(array $filters = []): array
    {
        $query = EmergencyCase::query()
            ->with(['patient', 'visit', 'bay', 'assignedDoctor', 'assignedNurse', 'latestVitals'])
            ->withCount([
                'clinicalTasks as due_tasks_count' => fn ($q) => $q->whereIn('status', [
                    ClinicalTask::STATUS_DUE,
                    ClinicalTask::STATUS_OVERDUE,
                ]),
                'medicationSchedules as active_medication_tasks_count' => fn ($q) => $q->whereIn('status', ['SCHEDULED', 'DUE', 'OVERDUE']),
                'labRequests as pending_investigations_count' => fn ($q) => $q->whereIn('status', ['pending', 'processing']),
                'procedureRequests as pending_procedures_count' => fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']),
            ])
            ->active()
            ->latest('arrival_time');

        if (! empty($filters['triage_category'])) {
            $query->where('triage_category', $filters['triage_category']);
        }

        if (! empty($filters['status'])) {
            $query->where('emergency_status', $filters['status']);
        }

        if (! empty($filters['bay_id'])) {
            $query->where('emergency_bay_id', $filters['bay_id']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('emergency_number', 'like', "%{$term}%")
                    ->orWhere('chief_complaint', 'like', "%{$term}%")
                    ->orWhereHas('patient', fn ($patient) => $patient
                        ->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('patient_number', 'like', "%{$term}%"));
            });
        }

        $cases = $query->paginate($filters['per_page'] ?? 20)->withQueryString();

        return [
            'cases' => $cases,
            'counts' => $this->counts(),
        ];
    }

    public function counts(): array
    {
        return [
            'active' => EmergencyCase::active()->count(),
            'waiting_triage' => EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_WAITING_TRIAGE)->count(),
            'red' => EmergencyCase::active()->where('triage_category', EmergencyCase::TRIAGE_RED)->count(),
            'under_care' => EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_UNDER_CARE)->count(),
            'observation' => EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_OBSERVATION)->count(),
            'ready_for_disposition' => EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_READY_FOR_DISPOSITION)->count(),
        ];
    }
}
