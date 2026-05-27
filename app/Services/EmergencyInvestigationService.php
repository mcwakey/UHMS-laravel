<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\LabRequest;
use App\Models\User;

class EmergencyInvestigationService
{
    public function __construct(
        private LabService $labs,
        private EmergencyTimelineService $timeline,
    ) {}

    public function request(EmergencyCase $case, array $data, User $user): LabRequest
    {
        $request = $this->labs->createRequest($case->visit, [$data['test_name']], [
            'target_department_id' => $data['target_department_id'] ?? null,
            'clinical_info' => $data['clinical_info'] ?? $case->chief_complaint,
            'urgency' => $data['urgency'] ?? 'emergency',
            'is_emergency' => true,
            'emergency_case_id' => $case->id,
        ]);

        $this->timeline->record($case, 'INVESTIGATION_REQUESTED', 'Emergency investigation requested', $data['test_name'], $request, $user);

        return $request;
    }
}
