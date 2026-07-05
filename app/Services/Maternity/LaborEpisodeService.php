<?php

namespace App\Services\Maternity;

use App\Enums\AdmissionRequestSource;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\LogModule;
use App\Enums\MaternityCaseType;
use App\Models\AdmissionRequest;
use App\Models\AntenatalVisit;
use App\Models\LaborEpisode;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Admissions\AdmissionRequestService;
use Illuminate\Support\Carbon;

class LaborEpisodeService
{
    public function __construct(
        private ActivityLogService $logger,
        private LaborRiskAssessmentService $riskAssessment,
        private MaternityCaseService $maternityCases,
        private AdmissionRequestService $admissionRequests,
    ) {}

    public function start(PregnancyProfile $profile, array $data, User $user, ?AntenatalVisit $antenatalVisit = null): LaborEpisode
    {
        $data = $this->normalise($profile, $data, null, $antenatalVisit);
        $assessment = $this->riskAssessment->assess($data);
        $data['risk_level'] = $data['risk_level'] ?? $assessment['risk_level']->value;
        $data['status'] = $data['status'] ?? LaborEpisodeStatus::ACTIVE->value;
        $data['labor_stage'] = $data['labor_stage'] ?? LaborStage::UNKNOWN->value;
        $data['started_by'] = $user->id;
        $data['updated_by'] = $user->id;
        $data['started_at'] = $data['started_at'] ?? now();

        $episode = LaborEpisode::create($data);
        $this->ensureLaborCase($episode, $user);
        $this->log($episode->fresh($this->relations()), 'LABOR_EPISODE_STARTED', $user, $assessment);

        return $episode->fresh($this->relations());
    }

    public function update(LaborEpisode $episode, array $data, User $user): LaborEpisode
    {
        $data = $this->normalise($episode->pregnancyProfile, $data, $episode, $episode->antenatalVisit);
        $assessment = $this->riskAssessment->assess($data + $episode->getAttributes());
        $data['risk_level'] = $data['risk_level'] ?? $assessment['risk_level']->value;
        $data['updated_by'] = $user->id;

        $episode->update($data);
        $this->log($episode->fresh($this->relations()), 'LABOR_EPISODE_UPDATED', $user, $assessment);

        return $episode->fresh($this->relations());
    }

    public function changeStage(LaborEpisode $episode, LaborStage $stage, ?LaborEpisodeStatus $status, User $user): LaborEpisode
    {
        $episode->update([
            'labor_stage' => $stage,
            'status' => $status ?? $episode->status,
            'updated_by' => $user->id,
        ]);

        $this->log($episode->fresh($this->relations()), 'LABOR_STAGE_CHANGED', $user);

        return $episode->fresh($this->relations());
    }

    public function markEscalation(LaborEpisode $episode, string $type, User $user): LaborEpisode
    {
        $field = $type === 'emergency' ? 'emergency_escalation_required' : 'theatre_escalation_required';
        $episode->update([
            $field => true,
            'status' => LaborEpisodeStatus::MONITORING,
            'updated_by' => $user->id,
        ]);

        $this->log($episode->fresh($this->relations()), $type === 'emergency' ? 'LABOR_EMERGENCY_ESCALATION_MARKED' : 'LABOR_THEATRE_ESCALATION_MARKED', $user);

        return $episode->fresh($this->relations());
    }

    public function close(LaborEpisode $episode, ?string $reason, User $user): LaborEpisode
    {
        $episode->update([
            'status' => LaborEpisodeStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closure_reason' => $reason ?: $episode->closure_reason,
            'updated_by' => $user->id,
        ]);

        $this->log($episode->fresh($this->relations()), 'LABOR_EPISODE_CLOSED', $user);

        return $episode->fresh($this->relations());
    }

    public function cancel(LaborEpisode $episode, ?string $reason, User $user): LaborEpisode
    {
        $episode->update([
            'status' => LaborEpisodeStatus::CANCELLED,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closure_reason' => $reason ?: $episode->closure_reason,
            'updated_by' => $user->id,
        ]);

        $this->log($episode->fresh($this->relations()), 'LABOR_EPISODE_CANCELLED', $user);

        return $episode->fresh($this->relations());
    }

    public function createAdmissionRequest(LaborEpisode $episode, array $data, User $user): AdmissionRequest
    {
        $episode = $episode->fresh($this->relations());
        $payload = [
            'patient_id' => $episode->patient_id,
            'visit_id' => $episode->visit_id,
            'source_type' => AdmissionRequestSource::MATERNITY->value,
            'source_id' => $episode->id,
            'requested_ward_id' => $data['requested_ward_id'] ?? null,
            'priority' => $data['priority'] ?? 'urgent',
            'provisional_diagnosis' => $data['provisional_diagnosis'] ?? __('maternity.labor_default_admission_diagnosis'),
            'clinical_summary' => $data['clinical_summary'] ?? $episode->clinical_summary ?? $episode->initial_assessment,
        ];

        $request = $episode->visit
            ? $this->admissionRequests->createForVisit($episode->visit, AdmissionRequestSource::MATERNITY, $episode->id, $payload, $user)
            : $this->admissionRequests->create($payload, $user);

        $this->log($episode, 'LABOR_ADMISSION_REQUEST_CREATED', $user, [
            'metadata' => ['admission_request_id' => $request->id],
        ]);

        return $request;
    }

    public function relations(): array
    {
        return [
            'patient',
            'pregnancyProfile',
            'maternityCase',
            'antenatalVisit',
            'visit',
            'admission.bed.ward',
            'department',
            'startedBy',
            'latestObservation',
            'deliveryRecords',
        ];
    }

    private function normalise(PregnancyProfile $profile, array $data, ?LaborEpisode $existing = null, ?AntenatalVisit $antenatalVisit = null): array
    {
        $data['pregnancy_profile_id'] = $profile->id;
        $data['patient_id'] = $profile->patient_id;
        $data['maternity_case_id'] = $data['maternity_case_id'] ?? $antenatalVisit?->maternity_case_id ?? $existing?->maternity_case_id;
        $data['antenatal_visit_id'] = $data['antenatal_visit_id'] ?? $antenatalVisit?->id ?? $existing?->antenatal_visit_id;
        $data['visit_id'] = $data['visit_id'] ?? $antenatalVisit?->visit_id ?? $profile->visit_id;
        $data['admission_id'] = $data['admission_id'] ?? $antenatalVisit?->admission_id ?? $profile->admission_id ?? $profile->patient?->activeAdmission?->id;
        $data['department_id'] = $data['department_id'] ?? $antenatalVisit?->department_id ?? $profile->department_id;

        foreach (['started_at', 'labor_onset_at', 'rupture_of_membranes_at', 'contractions_started_at'] as $key) {
            if (! empty($data[$key])) {
                $data[$key] = Carbon::parse($data[$key]);
            }
        }

        foreach (['maternity_case_id', 'antenatal_visit_id', 'visit_id', 'admission_id', 'department_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ?: null;
        }

        foreach (['membranes_status', 'liquor_colour', 'presentation', 'labor_stage', 'status', 'risk_level', 'delivery_mode_planned'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        $data['complications'] = $this->arrayValues($data['complications'] ?? []);

        return $data;
    }

    private function ensureLaborCase(LaborEpisode $episode, User $user): void
    {
        if ($episode->maternity_case_id) {
            return;
        }

        $case = $episode->pregnancyProfile->maternityCases()
            ->open()
            ->where('case_type', MaternityCaseType::LABOR_OBSERVATION->value)
            ->first();

        if (! $case) {
            $case = $this->maternityCases->open([
                'pregnancy_profile_id' => $episode->pregnancy_profile_id,
                'patient_id' => $episode->patient_id,
                'visit_id' => $episode->visit_id,
                'admission_id' => $episode->admission_id,
                'department_id' => $episode->department_id,
                'case_type' => MaternityCaseType::LABOR_OBSERVATION->value,
                'risk_level' => $episode->risk_level?->value,
                'clinical_summary' => $episode->clinical_summary ?: $episode->initial_assessment,
            ], $user);
        }

        $episode->updateQuietly(['maternity_case_id' => $case->id]);
    }

    private function arrayValues(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }

    private function log(LaborEpisode $episode, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $episode->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'labor_episode_id' => $episode->id,
                'pregnancy_profile_id' => $episode->pregnancy_profile_id,
                'status' => $episode->status?->value,
                'labor_stage' => $episode->labor_stage?->value,
                'risk_level' => $episode->risk_level?->value,
                'theatre_escalation_required' => $episode->theatre_escalation_required,
                'emergency_escalation_required' => $episode->emergency_escalation_required,
            ]),
            'causer' => $user,
        ], $episode, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
