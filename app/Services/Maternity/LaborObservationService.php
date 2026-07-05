<?php

namespace App\Services\Maternity;

use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborObservationStatus;
use App\Enums\LogModule;
use App\Enums\MaternityRiskLevel;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;

class LaborObservationService
{
    public function __construct(
        private ActivityLogService $logger,
        private LaborRiskAssessmentService $riskAssessment,
    ) {}

    public function create(LaborEpisode $episode, array $data, User $user): LaborObservation
    {
        $data = $this->normalise($episode, $data);
        $assessment = $this->riskAssessment->assess($data);
        $data['risk_flags'] = $assessment['risk_flags'];
        $data['danger_signs'] = $assessment['danger_signs'];
        $data['status'] = $data['status'] ?? (
            $assessment['requires_escalation_warning']
                ? LaborObservationStatus::ESCALATED->value
                : LaborObservationStatus::RECORDED->value
        );
        $data['recorded_by'] = $data['recorded_by'] ?? $user->id;

        $observation = LaborObservation::create($data);
        $this->syncEpisode($episode, $observation, $assessment, $user);
        $this->log($observation->fresh($this->relations()), 'LABOR_OBSERVATION_RECORDED', $user, $assessment);

        return $observation->fresh($this->relations());
    }

    public function update(LaborObservation $observation, array $data, User $user): LaborObservation
    {
        $data = $this->normalise($observation->laborEpisode, $data, $observation);
        $assessment = $this->riskAssessment->assess($data + $observation->getAttributes());
        $data['risk_flags'] = $assessment['risk_flags'];
        $data['danger_signs'] = $assessment['danger_signs'];
        $data['status'] = $data['status'] ?? (
            $assessment['requires_escalation_warning']
                ? LaborObservationStatus::ESCALATED->value
                : $observation->status?->value
        );

        $observation->update($data);
        $this->syncEpisode($observation->laborEpisode, $observation->fresh(), $assessment, $user);
        $this->log($observation->fresh($this->relations()), 'LABOR_OBSERVATION_UPDATED', $user, $assessment);

        return $observation->fresh($this->relations());
    }

    public function cancel(LaborObservation $observation, ?string $reason, User $user): LaborObservation
    {
        $notes = trim(collect([$observation->notes, $reason])->filter()->implode("\n"));
        $observation->update([
            'status' => LaborObservationStatus::CANCELLED,
            'notes' => $notes ?: $observation->notes,
        ]);

        $this->log($observation->fresh($this->relations()), 'LABOR_OBSERVATION_CANCELLED', $user);

        return $observation->fresh($this->relations());
    }

    public function relations(): array
    {
        return ['laborEpisode', 'pregnancyProfile', 'patient', 'visit', 'admission', 'recordedBy'];
    }

    private function normalise(LaborEpisode $episode, array $data, ?LaborObservation $existing = null): array
    {
        $data['labor_episode_id'] = $episode->id;
        $data['pregnancy_profile_id'] = $episode->pregnancy_profile_id;
        $data['patient_id'] = $episode->patient_id;
        $data['visit_id'] = $data['visit_id'] ?? $episode->visit_id;
        $data['admission_id'] = $data['admission_id'] ?? $episode->admission_id;
        $data['observed_at'] = Carbon::parse($data['observed_at'] ?? $existing?->observed_at ?? now());
        $data['risk_flags'] = $this->arrayValues($data['risk_flags'] ?? []);
        $data['danger_signs'] = $this->arrayValues($data['danger_signs'] ?? []);

        foreach (['visit_id', 'admission_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ?: null;
        }

        foreach (['labor_stage', 'membranes_status', 'liquor_colour', 'urine_protein', 'urine_glucose', 'status'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function syncEpisode(LaborEpisode $episode, LaborObservation $observation, array $assessment, User $user): void
    {
        $updates = ['updated_by' => $user->id];
        if ($observation->labor_stage) {
            $updates['labor_stage'] = $observation->labor_stage;
        }
        if (in_array($assessment['risk_level'], [MaternityRiskLevel::HIGH, MaternityRiskLevel::EMERGENCY], true)) {
            $updates['risk_level'] = $assessment['risk_level'];
            $updates['status'] = LaborEpisodeStatus::MONITORING;
        }

        $episode->update($updates);
    }

    private function arrayValues(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }

    private function log(LaborObservation $observation, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $observation->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'labor_observation_id' => $observation->id,
                'labor_episode_id' => $observation->labor_episode_id,
                'pregnancy_profile_id' => $observation->pregnancy_profile_id,
                'status' => $observation->status?->value,
                'labor_stage' => $observation->labor_stage?->value,
                'danger_signs_count' => count($observation->danger_signs ?? []),
                'risk_flags_count' => count($observation->risk_flags ?? []),
            ]),
            'causer' => $user,
        ], $observation, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
