<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\PatientMergeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmergencyPatientIdentityService
{
    public function __construct(
        private PatientMergeService $patientMergeService,
        private PatientService $patientService,
        private EmergencyTimelineService $timeline,
    ) {}

    public function confirm(EmergencyCase $emergencyCase, Patient $confirmedPatient, User $user, ?string $reason = null): PatientMergeRequest
    {
        $temporaryPatient = $emergencyCase->patient;

        $this->assertTemporaryPatient($temporaryPatient);

        if ($confirmedPatient->is_temporary) {
            throw new \InvalidArgumentException('Choose a registered patient folder, not another temporary emergency folder.');
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

    public function registerNewPatient(EmergencyCase $emergencyCase, array $patientData, User $user, ?string $reason = null): PatientMergeRequest
    {
        return DB::transaction(function () use ($emergencyCase, $patientData, $user, $reason) {
            $temporaryPatient = $emergencyCase->patient;
            $this->assertTemporaryPatient($temporaryPatient);

            $patientData['status'] = 'active';
            $patientData['is_temporary'] = false;
            $patientData['identity_confirmed_at'] = now();
            $patientData['identity_confirmed_by'] = $user->id;

            $registeredPatient = $this->patientService->create($patientData);
            $mergeRequest = $this->confirm(
                $emergencyCase,
                $registeredPatient,
                $user,
                $reason ?: 'Temporary emergency patient registered as a confirmed patient folder.',
            );

            $this->timeline->record(
                $emergencyCase->fresh(),
                'IDENTITY_REGISTERED',
                'Emergency patient registered',
                "Temporary folder {$temporaryPatient->patient_number} registered as {$registeredPatient->patient_number}.",
                $mergeRequest,
                $user,
            );

            return $mergeRequest;
        });
    }

    private function assertTemporaryPatient(?Patient $patient): void
    {
        if (! $patient) {
            throw new \InvalidArgumentException('This emergency case is not linked to a patient folder.');
        }

        if (! $patient->is_temporary) {
            throw new \InvalidArgumentException('Only temporary emergency patient folders can be confirmed through this workflow.');
        }
    }
}
