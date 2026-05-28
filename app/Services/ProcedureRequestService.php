<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Enums\ProcedureStatus;
use App\Models\Department;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Handles the doctor's side: requesting a procedure from the consultation page.
 */
class ProcedureRequestService
{
    public function __construct(
        protected ProcedureWorkflowService $workflow,
    ) {}

    /**
     * Create a new procedure request in REQUESTED status.
     *
     * Required keys: visit_id, department_id, service_catalog_id, priority, indication
     * Optional: notes, preferred_datetime, procedure_id (legacy catalog)
     */
    public function requestProcedure(array $data, User $doctor): ProcedureRequest
    {
        $visit = Visit::findOrFail($data['visit_id']);
        $department = Department::findOrFail($data['department_id']);
        $service = ServiceCatalog::findOrFail($data['service_catalog_id']);

        // Department must be a theatre/procedure department.
        if (! $this->isProcedureDepartment($department)) {
            throw new \InvalidArgumentException('Selected department is not a procedure/theatre department.');
        }

        // Service must belong to the department (catalog department_id) when set.
        if ($service->department_id && (int) $service->department_id !== (int) $department->id) {
            throw new \InvalidArgumentException('Selected service does not belong to the selected procedure department.');
        }

        if (empty(trim((string) ($data['indication'] ?? '')))) {
            throw new \InvalidArgumentException('Indication / reason for the procedure is required.');
        }

        return DB::transaction(function () use ($visit, $department, $service, $data, $doctor) {
            $now = now();

            $request = ProcedureRequest::create([
                'request_number'     => ProcedureRequest::generateNumber(),
                'visit_id'           => $visit->id,
                'emergency_case_id'  => $data['emergency_case_id'] ?? null,
                'emergency_session_id' => $data['emergency_session_id'] ?? null,
                'patient_id'         => $visit->patient_id,
                'requested_by'       => $doctor->id,
                'department_id'      => $department->id,
                'service_catalog_id' => $service->id,
                'procedure_id'       => $data['procedure_id'] ?? null,
                'priority'           => $data['priority'] ?? 'routine',
                'is_emergency'       => (bool) ($data['is_emergency'] ?? (($data['priority'] ?? null) === 'emergency')),
                'indication'         => trim((string) $data['indication']),
                'notes'              => $data['notes'] ?? null,
                'preferred_datetime' => $data['preferred_datetime'] ?? null,
                'status'             => ProcedureStatus::REQUESTED,
                'requested_at'       => $now,
            ]);

            $this->workflow->logStatusChange($request, null, ProcedureStatus::REQUESTED, $doctor, 'Procedure requested by doctor.');

            return $request->fresh(['service', 'department', 'requestingDoctor']);
        });
    }

    /**
     * List procedure requests for a visit (used on consultation page).
     */
    public function forVisit(int $visitId)
    {
        return ProcedureRequest::with([
            'service', 'department', 'requestingDoctor',
            'schedule.theatreRoom', 'schedule.surgeon', 'schedule.anaesthetist',
            'billingItem',
        ])
            ->where('visit_id', $visitId)
            ->latest('id')
            ->get();
    }

    /**
     * Procedure departments (any Department with type=procedure).
     */
    public function procedureDepartments()
    {
        return Department::where('type', DepartmentType::PROCEDURE->value)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Services belonging to a given procedure department.
     */
    public function servicesForDepartment(int $departmentId)
    {
        return ServiceCatalog::where('department_id', $departmentId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function isProcedureDepartment(Department $department): bool
    {
        $type = $department->type instanceof DepartmentType ? $department->type->value : (string) $department->type;
        return $type === DepartmentType::PROCEDURE->value;
    }
}
