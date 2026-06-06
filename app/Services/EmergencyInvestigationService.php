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

        // Accept one or many selected services → a single lab request with one
        // item per selected test (the natural lab-request model).
        $serviceIds = array_values(array_unique(array_filter((array) ($data['service_id'] ?? []))));
        $services = ServiceCatalog::whereIn('id', $serviceIds)->get();
        if ($services->isEmpty()) {
            throw new \InvalidArgumentException('Select at least one investigation service.');
        }

        $items = $services->map(fn (ServiceCatalog $service) => [
            'service_id' => $service->id,
            'name' => $service->name,
        ])->all();

        $request = $this->labs->createRequest($case->visit, $items, [
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
        $this->timeline->record($case, 'INVESTIGATION_REQUESTED', 'Emergency investigation requested', $services->pluck('name')->implode(', '), $request, $user);

        return $request;
    }
}
