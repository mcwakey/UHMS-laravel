<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\ClinicalTask;
use App\Models\EmergencyCase;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EmergencyBoardService
{
    public function __construct(
        private DepartmentContextSwitcherService $departments,
        private Request $request,
    ) {}

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

        $this->scopeToActiveEmergencyDepartment($query);

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
        $active = EmergencyCase::active();
        $waitingTriage = EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_WAITING_TRIAGE);
        $red = EmergencyCase::active()->where('triage_category', EmergencyCase::TRIAGE_RED);
        $underCare = EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_UNDER_CARE);
        $observation = EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_OBSERVATION);
        $readyForDisposition = EmergencyCase::active()->where('emergency_status', EmergencyCase::STATUS_READY_FOR_DISPOSITION);

        foreach ([$active, $waitingTriage, $red, $underCare, $observation, $readyForDisposition] as $query) {
            $this->scopeToActiveEmergencyDepartment($query);
        }

        return [
            'active' => $active->count(),
            'waiting_triage' => $waitingTriage->count(),
            'red' => $red->count(),
            'under_care' => $underCare->count(),
            'observation' => $observation->count(),
            'ready_for_disposition' => $readyForDisposition->count(),
        ];
    }

    private function scopeToActiveEmergencyDepartment(Builder $query): void
    {
        $user = $this->request->user();
        if (! $user || $user->isAdminUser()) {
            return;
        }

        $department = $this->departments->currentDepartment($user, $this->request);
        $type = $department?->type instanceof DepartmentType
            ? $department->type
            : DepartmentType::tryFrom((string) ($department?->type ?? ''));

        if (! $department || $type !== DepartmentType::EMERGENCY) {
            return;
        }

        $query->where(function (Builder $scope) use ($department) {
            $scope->whereHas('visit', fn (Builder $visit) => $visit->where('current_department_id', $department->id))
                ->orWhereHas('activeEmergencySession', fn (Builder $session) => $session->where('department_id', $department->id));
        });
    }
}
