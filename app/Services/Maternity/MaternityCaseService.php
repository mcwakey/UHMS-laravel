<?php

namespace App\Services\Maternity;

use App\Enums\AdmissionRequestSource;
use App\Enums\LogModule;
use App\Enums\MaternityCaseStatus;
use App\Enums\MaternityCaseType;
use App\Enums\MaternitySourceType;
use App\Models\AdmissionRequest;
use App\Models\MaternityCase;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Admissions\AdmissionRequestService;

class MaternityCaseService
{
    public function __construct(
        private ActivityLogService $logger,
        private AdmissionRequestService $admissionRequests,
    ) {}

    public function open(array $data, User $user): MaternityCase
    {
        $data['opened_by'] = $user->id;
        $data['opened_at'] ??= now();
        $data['status'] ??= MaternityCaseStatus::OPEN->value;

        if (! empty($data['pregnancy_profile_id'])) {
            $profile = PregnancyProfile::find($data['pregnancy_profile_id']);
            if ($profile) {
                $data['patient_id'] = $data['patient_id'] ?? $profile->patient_id;
                $data['visit_id'] = $data['visit_id'] ?? $profile->visit_id;
                $data['admission_id'] = $data['admission_id'] ?? $profile->admission_id;
                $data['department_id'] = $data['department_id'] ?? $profile->department_id;
                $data['source_type'] = $data['source_type'] ?? MaternitySourceType::PREGNANCY_PROFILE->value;
                $data['source_id'] = $data['source_id'] ?? $profile->id;
            }
        }

        $case = MaternityCase::create($data);
        $this->log($case, 'MATERNITY_CASE_OPENED', $user);

        return $case->fresh(['patient', 'pregnancyProfile', 'visit', 'admission', 'department']);
    }

    public function update(MaternityCase $case, array $data, User $user): MaternityCase
    {
        $case->update($data);
        $this->log($case, 'MATERNITY_CASE_UPDATED', $user);

        return $case->fresh(['patient', 'pregnancyProfile', 'visit', 'admission', 'department']);
    }

    public function close(MaternityCase $case, MaternityCaseStatus $status, ?string $reason, User $user): MaternityCase
    {
        $case->update([
            'status' => $status,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'reason' => $reason ?: $case->reason,
        ]);

        $this->log($case, $status === MaternityCaseStatus::CANCELLED ? 'MATERNITY_CASE_CANCELLED' : 'MATERNITY_CASE_CLOSED', $user, [
            'metadata' => ['status' => $status->value, 'has_reason' => filled($reason)],
        ]);

        return $case->fresh(['patient', 'pregnancyProfile', 'visit', 'admission', 'department']);
    }

    public function createAdmissionRequest(MaternityCase $case, array $data, User $user): AdmissionRequest
    {
        $payload = [
            'patient_id' => $case->patient_id,
            'visit_id' => $case->visit_id,
            'source_type' => AdmissionRequestSource::MATERNITY->value,
            'source_id' => $case->id,
            'requested_ward_id' => $data['requested_ward_id'] ?? null,
            'priority' => $data['priority'] ?? $case->priority,
            'provisional_diagnosis' => $data['provisional_diagnosis'] ?? __('maternity.default_admission_diagnosis'),
            'clinical_summary' => $data['clinical_summary'] ?? $case->clinical_summary,
        ];

        $request = $case->visit
            ? $this->admissionRequests->createForVisit($case->visit, AdmissionRequestSource::MATERNITY, $case->id, $payload, $user)
            : $this->admissionRequests->create($payload, $user);

        $this->log($case, 'MATERNITY_ADMISSION_REQUEST_CREATED', $user, [
            'metadata' => ['admission_request_id' => $request->id],
        ]);

        return $request;
    }

    private function log(MaternityCase $case, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $case->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'maternity_case_id' => $case->id,
                'status' => $case->status?->value,
                'case_type' => $case->case_type?->value,
                'risk_level' => $case->risk_level?->value,
            ]),
            'causer' => $user,
        ], $case, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
