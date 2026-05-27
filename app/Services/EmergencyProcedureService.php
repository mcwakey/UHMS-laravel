<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\ProcedureRequest;
use App\Models\User;

class EmergencyProcedureService
{
    public function __construct(
        private ProcedureRequestService $procedures,
        private EmergencyTimelineService $timeline,
    ) {}

    public function request(EmergencyCase $case, array $data, User $user): ProcedureRequest
    {
        $request = $this->procedures->requestProcedure([
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'department_id' => $data['department_id'],
            'service_catalog_id' => $data['service_catalog_id'],
            'priority' => $data['priority'] ?? 'emergency',
            'is_emergency' => true,
            'indication' => $data['indication'],
            'notes' => $data['notes'] ?? null,
            'preferred_datetime' => $data['preferred_datetime'] ?? null,
        ], $user);

        $this->timeline->record($case, 'PROCEDURE_REQUESTED', 'Emergency procedure requested', $request->service?->name, $request, $user);

        return $request;
    }
}
