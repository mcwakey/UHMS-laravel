<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\User;

class VisitGuardService
{
    public const ACTIVE_ADMISSION_MESSAGE = 'This patient is currently admitted and has not yet been discharged. A new OPD visit cannot be created until the admission is completed.';

    public function __construct(
        private PatientMergeGuard $patientMergeGuard,
        private ?ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(ActivityLogService::class);
    }

    public function assertCanCreateVisit(Patient|int $patient, ?User $user = null, ?string $overrideReason = null): Patient
    {
        $patient = $this->patientMergeGuard->assertCanReceiveNewRecords($patient, 'visit');
        $activeAdmission = $this->activeAdmissionFor($patient);

        if (! $activeAdmission) {
            return $patient;
        }

        if (! $user || ! $user->can('visits.create_while_admitted')) {
            throw new \InvalidArgumentException(self::ACTIVE_ADMISSION_MESSAGE);
        }

        if (trim((string) $overrideReason) === '') {
            throw new \InvalidArgumentException('A reason is required to create an OPD visit while the patient is actively admitted.');
        }

        $this->logger?->log(LogModule::SYSTEM, 'VISIT_CREATED_WHILE_ADMITTED_OVERRIDE', [
            'patient_id' => $patient->id,
            'admission_id' => $activeAdmission->id,
            'reason' => $overrideReason,
            'causer' => $user,
        ], $patient, 'OPD visit creation allowed while patient was admitted');

        return $patient;
    }

    public function activeAdmissionFor(Patient $patient): ?Admission
    {
        return Admission::query()
            ->with(['bed.ward'])
            ->where('patient_id', $patient->id)
            ->active()
            ->latest('admission_date')
            ->first();
    }
}
