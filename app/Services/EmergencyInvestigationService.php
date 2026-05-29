<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\LabRequest;
use App\Models\ServiceCatalog;
use App\Models\User;

class EmergencyInvestigationService
{
    public function __construct(
        private LabService $labs,
        private EmergencyTimelineService $timeline,
        private EmergencySessionService $sessions,
    ) {}

    public function request(EmergencyCase $case, array $data, User $user): LabRequest
    {
        $session = $this->sessions->getOrCreateForCase($case, $user);
        $service = ServiceCatalog::findOrFail($data['service_id']);

        $request = $this->labs->createRequest($case->visit, [[
            'service_id' => $service->id,
            'name' => $service->name,
        ]], [
            'target_department_id' => $data['target_department_id'] ?? null,
            'clinical_info' => $data['clinical_info'] ?? $case->chief_complaint,
            'urgency' => $data['urgency'] ?? 'emergency',
            'is_emergency' => true,
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $session->id,
            'medical_record_id' => $session->medical_record_id,
            'consultation_route_id' => $session->consultation_route_id,
        ]);

        $this->sessions->recordContribution($case, $user, 'Investigation');
        $this->timeline->record($case, 'INVESTIGATION_REQUESTED', 'Emergency investigation requested', $service->name, $request, $user);

        return $request;
    }
}
