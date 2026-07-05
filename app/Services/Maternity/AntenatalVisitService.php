<?php

namespace App\Services\Maternity;

use App\Enums\AdmissionRequestSource;
use App\Enums\AntenatalReferralType;
use App\Enums\AntenatalVisitStatus;
use App\Enums\LogModule;
use App\Enums\MaternityCaseType;
use App\Enums\MaternityRiskLevel;
use App\Enums\PregnancyProfileStatus;
use App\Models\AdmissionRequest;
use App\Models\AntenatalVisit;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Admissions\AdmissionRequestService;
use Illuminate\Support\Carbon;

class AntenatalVisitService
{
    public function __construct(
        private ActivityLogService $logger,
        private AntenatalRiskAssessmentService $riskAssessment,
        private AdmissionRequestService $admissionRequests,
        private MaternityCaseService $maternityCases,
    ) {}

    public function create(PregnancyProfile $profile, array $data, User $user): AntenatalVisit
    {
        $data = $this->normalise($profile, $data);
        $assessment = $this->riskAssessment->assess($data);
        $data['risk_flags'] = $assessment['risk_flags'];
        $data['status'] = $data['status'] ?? $this->statusFor($data, $assessment)->value;
        $data['recorded_by'] = $data['recorded_by'] ?? $user->id;
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        $visit = AntenatalVisit::create($data);
        $this->syncProfile($profile, $visit, $assessment, $user);
        $this->log($visit, 'ANC_VISIT_RECORDED', $user, $assessment);

        return $visit->fresh($this->relations());
    }

    public function update(AntenatalVisit $visit, array $data, User $user): AntenatalVisit
    {
        $profile = $visit->pregnancyProfile;
        $data = $this->normalise($profile, $data, $visit);
        $assessment = $this->riskAssessment->assess(array_merge($visit->toArray(), $data));
        $data['risk_flags'] = $assessment['risk_flags'];
        $data['status'] = $data['status'] ?? $this->statusFor($data + $visit->getAttributes(), $assessment)->value;
        $data['updated_by'] = $user->id;

        $visit->update($data);
        $this->syncProfile($profile, $visit->fresh(), $assessment, $user);
        $this->log($visit->fresh(), 'ANC_VISIT_UPDATED', $user, $assessment);

        return $visit->fresh($this->relations());
    }

    public function cancel(AntenatalVisit $visit, ?string $reason, User $user): AntenatalVisit
    {
        $visit->update([
            'status' => AntenatalVisitStatus::CANCELLED,
            'updated_by' => $user->id,
            'referral_reason' => $reason ?: $visit->referral_reason,
        ]);

        $this->log($visit->fresh(), 'ANC_VISIT_CANCELLED', $user);

        return $visit->fresh($this->relations());
    }

    public function recordReferral(AntenatalVisit $visit, array $data, User $user): AntenatalVisit
    {
        $visit->update([
            'referral_type' => $data['referral_type'] ?? AntenatalReferralType::NONE->value,
            'referral_reason' => $data['referral_reason'] ?? null,
            'status' => AntenatalVisitStatus::REFERRED,
            'updated_by' => $user->id,
        ]);

        $this->ensureAntenatalCase($visit->fresh(), $user);
        $this->log($visit->fresh(), 'ANC_REFERRAL_RECORDED', $user);

        return $visit->fresh($this->relations());
    }

    public function createAdmissionRequest(AntenatalVisit $visit, array $data, User $user): AdmissionRequest
    {
        $visit = $visit->fresh($this->relations());
        $this->ensureAntenatalCase($visit, $user);

        $payload = [
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->visit_id,
            'source_type' => AdmissionRequestSource::MATERNITY->value,
            'source_id' => $visit->id,
            'requested_ward_id' => $data['requested_ward_id'] ?? null,
            'priority' => $data['priority'] ?? 'urgent',
            'provisional_diagnosis' => $data['provisional_diagnosis'] ?? __('maternity.anc_default_admission_diagnosis'),
            'clinical_summary' => $data['clinical_summary'] ?? $visit->referral_reason ?? $visit->assessment,
        ];

        $request = $visit->visit
            ? $this->admissionRequests->createForVisit($visit->visit, AdmissionRequestSource::MATERNITY, $visit->id, $payload, $user)
            : $this->admissionRequests->create($payload, $user);

        $visit->update([
            'referral_type' => AntenatalReferralType::MATERNITY_ADMISSION,
            'status' => AntenatalVisitStatus::REFERRED,
            'updated_by' => $user->id,
        ]);

        $this->log($visit->fresh(), 'ANC_ADMISSION_REQUEST_CREATED', $user, [
            'metadata' => ['admission_request_id' => $request->id],
        ]);

        return $request;
    }

    public function relations(): array
    {
        return ['patient', 'pregnancyProfile', 'maternityCase', 'visit', 'admission', 'department', 'recordedBy'];
    }

    private function normalise(PregnancyProfile $profile, array $data, ?AntenatalVisit $existing = null): array
    {
        $data['pregnancy_profile_id'] = $profile->id;
        $data['patient_id'] = $profile->patient_id;
        $data['visit_id'] = $data['visit_id'] ?? $profile->visit_id;
        $data['admission_id'] = $data['admission_id'] ?? $profile->admission_id;
        $data['department_id'] = $data['department_id'] ?? $profile->department_id;
        $data['visit_date'] = Carbon::parse($data['visit_date'] ?? $existing?->visit_date ?? now());
        $data['visit_number'] = $data['visit_number'] ?? $existing?->visit_number ?? ($profile->antenatalVisits()->count() + 1);
        $data['danger_signs'] = $this->arrayValues($data['danger_signs'] ?? []);
        $data['risk_flags'] = $this->arrayValues($data['risk_flags'] ?? []);
        $data['supplements'] = $this->arrayValues($data['supplements'] ?? []);
        $data['immunisations'] = $this->arrayValues($data['immunisations'] ?? []);

        foreach (['maternity_case_id', 'visit_id', 'admission_id', 'department_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ?: null;
        }

        foreach (['presentation', 'urine_protein', 'urine_glucose', 'referral_type', 'status'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function arrayValues(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }

    private function statusFor(array $data, array $assessment): AntenatalVisitStatus
    {
        $referral = $data['referral_type'] ?? AntenatalReferralType::NONE->value;
        if ($referral && $referral !== AntenatalReferralType::NONE->value) {
            return AntenatalVisitStatus::REFERRED;
        }
        if (in_array($assessment['risk_level'], [MaternityRiskLevel::HIGH, MaternityRiskLevel::EMERGENCY], true)) {
            return AntenatalVisitStatus::HIGH_RISK;
        }
        if (! empty($data['next_visit_date'])) {
            return AntenatalVisitStatus::FOLLOW_UP_SCHEDULED;
        }

        return AntenatalVisitStatus::RECORDED;
    }

    private function syncProfile(PregnancyProfile $profile, AntenatalVisit $visit, array $assessment, User $user): void
    {
        $updates = ['updated_by' => $user->id];
        if ($visit->gestational_age_weeks !== null && $this->isLatestAnc($profile, $visit)) {
            $updates['gestational_age_weeks'] = $visit->gestational_age_weeks;
            $updates['gestational_age_days'] = $visit->gestational_age_days ?? 0;
        }

        if (in_array($assessment['risk_level'], [MaternityRiskLevel::HIGH, MaternityRiskLevel::EMERGENCY], true)) {
            $updates['profile_status'] = PregnancyProfileStatus::HIGH_RISK;
            $updates['known_risks'] = collect($profile->known_risks ?? [])
                ->merge($assessment['risk_flags'])
                ->merge($assessment['danger_signs'])
                ->unique()
                ->values()
                ->all();
        }

        if (count($updates) > 1) {
            $profile->update($updates);
        }
    }

    private function isLatestAnc(PregnancyProfile $profile, AntenatalVisit $visit): bool
    {
        $latest = $profile->antenatalVisits()->whereKeyNot($visit->id)->latest('visit_date')->first();

        return ! $latest || $visit->visit_date->gte($latest->visit_date);
    }

    private function ensureAntenatalCase(AntenatalVisit $visit, User $user): void
    {
        if ($visit->maternity_case_id) {
            return;
        }

        $case = $visit->pregnancyProfile->maternityCases()
            ->open()
            ->where('case_type', MaternityCaseType::ANTENATAL->value)
            ->first();

        if (! $case) {
            $case = $this->maternityCases->open([
                'pregnancy_profile_id' => $visit->pregnancy_profile_id,
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->visit_id,
                'admission_id' => $visit->admission_id,
                'department_id' => $visit->department_id,
                'case_type' => MaternityCaseType::ANTENATAL->value,
                'risk_level' => $visit->status === AntenatalVisitStatus::HIGH_RISK ? MaternityRiskLevel::HIGH->value : MaternityRiskLevel::LOW->value,
                'clinical_summary' => $visit->assessment,
            ], $user);
        }

        $visit->updateQuietly(['maternity_case_id' => $case->id]);
    }

    private function log(AntenatalVisit $visit, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $visit->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'antenatal_visit_id' => $visit->id,
                'pregnancy_profile_id' => $visit->pregnancy_profile_id,
                'status' => $visit->status?->value,
                'danger_signs_count' => count($visit->danger_signs ?? []),
                'risk_flags_count' => count($visit->risk_flags ?? []),
            ]),
            'causer' => $user,
        ], $visit, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
