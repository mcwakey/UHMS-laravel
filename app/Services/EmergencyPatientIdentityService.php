<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\PatientMergeRequest;
use App\Models\User;

class EmergencyPatientIdentityService
{
    public function __construct(private PatientMergeService $patientMergeService) {}

    public function confirm(EmergencyCase $emergencyCase, Patient $confirmedPatient, User $user, ?string $reason = null): PatientMergeRequest
    {
        $temporaryPatient = $emergencyCase->patient;

        if (! $temporaryPatient) {
            throw new \InvalidArgumentException('This emergency case is not linked to a patient folder.');
        }

        if (! $temporaryPatient->is_temporary) {
            throw new \InvalidArgumentException('Only temporary emergency patient folders can be confirmed through this workflow.');
        }

        $mergeRequest = $this->patientMergeService->createRequest(
            mainPatient: $confirmedPatient,
            duplicatePatient: $temporaryPatient,
            user: $user,
            fieldResolution: [],
            reason: $reason ?: 'Emergency temporary patient identity confirmed.',
            approve: true,
        );

        return $this->patientMergeService->execute($mergeRequest, $user);
    }
}
