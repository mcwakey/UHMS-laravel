<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\User;
use App\Models\Vital;

class EmergencyVitalsService
{
    public function __construct(
        private EmergencyTimelineService $timeline,
        private EmergencySessionService $sessions,
    ) {}

    public function record(EmergencyCase $case, array $data, User $user): Vital
    {
        $session = $this->sessions->getOrCreateForCase($case, $user);

        $vital = Vital::create([
            'visit_id' => $case->visit_id,
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $session->id,
            'patient_id' => $case->patient_id,
            'recorded_by' => $user->id,
            'blood_pressure_systolic' => $data['blood_pressure_systolic'] ?? null,
            'blood_pressure_diastolic' => $data['blood_pressure_diastolic'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'temperature' => $data['temperature'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'weight' => $data['weight'] ?? null,
            'height' => $data['height'] ?? null,
            'blood_sugar' => $data['blood_sugar'] ?? null,
            'notes' => $data['notes'] ?? null,
            'monitoring_context' => 'EMERGENCY_MONITORING',
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        $this->sessions->recordContribution($case, $user, 'Vitals');
        $this->timeline->record($case, 'VITALS_RECORDED', 'Emergency vitals recorded', $vital->blood_pressure ?: 'Vitals recorded', $vital, $user);

        return $vital;
    }
}
