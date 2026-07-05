<?php

namespace App\Services\Maternity;

use App\Enums\LogModule;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornOutcome;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PostnatalObservationStatus;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PostnatalNewbornObservation;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostnatalNewbornObservationService
{
    public function __construct(
        private ActivityLogService $logger,
        private PostnatalRiskAssessmentService $riskAssessment,
    ) {}

    public function create(PostnatalCase $case, NewbornRecord $newborn, array $data, User $user): PostnatalNewbornObservation
    {
        return DB::transaction(function () use ($case, $newborn, $data, $user) {
            $this->assertBelongsToCase($case, $newborn);
            $data = $this->normalise($case, $newborn, $data);
            $assessment = $this->riskAssessment->assessNewborn($data);
            $data['danger_signs'] = $assessment['danger_signs'];
            $data['risk_flags'] = $assessment['risk_flags'];
            $data['status'] = $assessment['requires_review'] ? PostnatalObservationStatus::REVIEW_REQUIRED : PostnatalObservationStatus::RECORDED;
            $data['observed_by'] = $data['observed_by'] ?? $user->id;
            $data['updated_by'] = $user->id;

            $observation = PostnatalNewbornObservation::create($data);
            $this->syncCaseRisk($case, $assessment, $user);
            $this->log($observation->fresh($this->relations()), 'POSTNATAL_NEWBORN_OBSERVATION_RECORDED', $user, $assessment);

            return $observation->fresh($this->relations());
        });
    }

    public function update(PostnatalNewbornObservation $observation, array $data, User $user): PostnatalNewbornObservation
    {
        return DB::transaction(function () use ($observation, $data, $user) {
            $data = $this->normalise($observation->postnatalCase, $observation->newbornRecord, $data + $observation->getAttributes());
            $assessment = $this->riskAssessment->assessNewborn($data);
            $data['danger_signs'] = $assessment['danger_signs'];
            $data['risk_flags'] = $assessment['risk_flags'];
            $data['status'] = $assessment['requires_review'] ? PostnatalObservationStatus::REVIEW_REQUIRED : PostnatalObservationStatus::RECORDED;
            $data['updated_by'] = $user->id;

            $observation->update($data);
            $this->syncCaseRisk($observation->postnatalCase, $assessment, $user);
            $this->log($observation->fresh($this->relations()), 'POSTNATAL_NEWBORN_OBSERVATION_UPDATED', $user, $assessment);

            return $observation->fresh($this->relations());
        });
    }

    public function cancel(PostnatalNewbornObservation $observation, ?string $reason, User $user): PostnatalNewbornObservation
    {
        $observation->update([
            'status' => PostnatalObservationStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        $this->log($observation->fresh($this->relations()), 'POSTNATAL_NEWBORN_OBSERVATION_CANCELLED', $user);

        return $observation->fresh($this->relations());
    }

    public function relations(): array
    {
        return ['postnatalCase', 'newbornRecord', 'deliveryRecord', 'pregnancyProfile', 'mother', 'newbornPatient', 'visit', 'admission', 'department', 'observedBy'];
    }

    private function normalise(PostnatalCase $case, NewbornRecord $newborn, array $data): array
    {
        if ($newborn->outcome === NewbornOutcome::STILLBIRTH) {
            throw ValidationException::withMessages(['newborn_record_id' => __('maternity.postnatal_stillbirth_observation_not_required')]);
        }

        $data['postnatal_case_id'] = $case->id;
        $data['newborn_record_id'] = $newborn->id;
        $data['delivery_record_id'] = $case->delivery_record_id;
        $data['pregnancy_profile_id'] = $case->pregnancy_profile_id;
        $data['mother_patient_id'] = $case->mother_patient_id;
        $data['newborn_patient_id'] = $newborn->newborn_patient_id;
        $data['visit_id'] = $case->visit_id;
        $data['admission_id'] = $case->admission_id;
        $data['department_id'] = $case->department_id;
        $data['observed_at'] = $data['observed_at'] ?? now();
        $data['danger_signs'] = $this->values($data['danger_signs'] ?? []);
        $data['risk_flags'] = $this->values($data['risk_flags'] ?? []);

        foreach (['feeding_status', 'breathing_status', 'cord_status', 'jaundice_status'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function assertBelongsToCase(PostnatalCase $case, NewbornRecord $newborn): void
    {
        if ((int) $newborn->delivery_record_id !== (int) $case->delivery_record_id) {
            throw ValidationException::withMessages(['newborn_record_id' => __('maternity.newborn_not_linked_to_postnatal_case')]);
        }
    }

    private function syncCaseRisk(PostnatalCase $case, array $assessment, User $user): void
    {
        $risk = $assessment['risk_level'];
        $updates = ['updated_by' => $user->id];
        if ($this->rank($risk) > $this->rank($case->risk_level ?? MaternityRiskLevel::LOW)) {
            $updates['risk_level'] = $risk;
        }
        if ($assessment['requires_review'] && ! $case->status?->isClosed()) {
            $updates['status'] = $assessment['referral_recommended'] ? PostnatalCaseStatus::REFERRAL_REQUIRED : PostnatalCaseStatus::UNDER_OBSERVATION;
        }
        if ($assessment['referral_recommended']) {
            $updates['referral_required'] = true;
            $updates['referral_reason'] = $case->referral_reason ?: __('maternity.postnatal_danger_signs_flagged');
            $updates['referral_marked_at'] = $case->referral_marked_at ?: now();
            $updates['referral_marked_by'] = $case->referral_marked_by ?: $user->id;
        }

        $case->update($updates);
    }

    private function rank(MaternityRiskLevel|string|null $risk): int
    {
        $risk = $risk instanceof MaternityRiskLevel ? $risk : MaternityRiskLevel::tryFrom((string) $risk);
        return match ($risk) {
            MaternityRiskLevel::EMERGENCY => 4,
            MaternityRiskLevel::HIGH => 3,
            MaternityRiskLevel::MODERATE => 2,
            default => 1,
        };
    }

    private function values(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }

    private function log(PostnatalNewbornObservation $observation, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $observation->toActivityContext() + [
            'metadata' => [
                'postnatal_case_id' => $observation->postnatal_case_id,
                'newborn_record_id' => $observation->newborn_record_id,
                'newborn_observation_id' => $observation->id,
                'status' => $observation->status?->value,
                'risk_flags_count' => count($observation->risk_flags ?? []),
                'danger_signs_count' => count($observation->danger_signs ?? []),
                'risk_level' => $extra['risk_level']?->value ?? null,
            ],
            'causer' => $user,
        ], $observation, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
