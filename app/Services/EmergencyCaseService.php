<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmergencyCaseService
{
    public function __construct(
        private EmergencyNumberService $numbers,
        private EmergencyTimelineService $timeline,
        private EmergencyBayService $bays,
        private PatientMergeGuard $patientMergeGuard,
    ) {}

    public function create(array $data, User $user): EmergencyCase
    {
        return DB::transaction(function () use ($data, $user) {
            $patient = empty($data['patient_id'])
                ? $this->createTemporaryPatient($data, $user)
                : Patient::findOrFail($data['patient_id']);

            $this->patientMergeGuard->assertCanReceiveNewRecords($patient, 'emergency case');

            $arrival = Carbon::parse($data['arrival_time'] ?? now());

            $visit = Visit::create([
                'visit_number' => Visit::generateVisitNumber(),
                'patient_id' => $patient->id,
                'patient_age' => $patient->date_of_birth?->age,
                'visit_type' => VisitType::EMERGENCY->value,
                'visit_date' => $arrival->toDateString(),
                'status' => VisitStatus::EMERGENCY->value,
                'priority' => Priority::EMERGENCY->value,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'notes' => $data['initial_condition'] ?? null,
                'checked_in_at' => $arrival,
                'created_by' => $user->id,
            ]);

            $case = EmergencyCase::create([
                'emergency_number' => $this->numbers->generateCaseNumber(),
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'arrival_mode' => $data['arrival_mode'],
                'arrival_time' => $arrival,
                'brought_by' => $data['brought_by'] ?? null,
                'source' => $data['source'] ?? null,
                'referral_facility' => $data['referral_facility'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'initial_condition' => $data['initial_condition'] ?? null,
                'emergency_status' => EmergencyCase::STATUS_WAITING_TRIAGE,
                'assigned_doctor_id' => $data['assigned_doctor_id'] ?? null,
                'assigned_nurse_id' => $data['assigned_nurse_id'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->timeline->record($case, 'ARRIVAL', 'Emergency case created', $case->chief_complaint, $case, $user);

            if (! empty($data['emergency_bay_id'])) {
                $this->bays->assign($case, (int) $data['emergency_bay_id'], $user, true);
            }

            return $case->fresh(['patient', 'visit', 'bay', 'assignedDoctor', 'assignedNurse']);
        });
    }

    private function createTemporaryPatient(array $data, User $user): Patient
    {
        $displayName = trim((string) ($data['temporary_display_name'] ?? 'Unknown Emergency Patient'));
        $parts = preg_split('/\s+/', $displayName, 2);
        $estimatedAge = max(0, min(120, (int) ($data['estimated_age'] ?? 30)));

        return Patient::create([
            'patient_number' => $this->numbers->generateTemporaryPatientNumber(),
            'first_name' => $parts[0] ?: 'Unknown',
            'last_name' => $parts[1] ?? 'Emergency',
            'date_of_birth' => now()->subYears($estimatedAge)->toDateString(),
            'gender' => $data['temporary_gender'] ?? 'male',
            'phone' => '0000000000',
            'status' => 'active',
            'is_temporary' => true,
            'temporary_reason' => $data['temporary_reason'] ?? 'Emergency identity not confirmed',
            'registered_by' => $user->id,
        ]);
    }
}
