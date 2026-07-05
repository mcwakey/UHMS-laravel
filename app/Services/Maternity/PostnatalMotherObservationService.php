<?php

namespace App\Services\Maternity;

use App\Enums\LogModule;
use App\Enums\MaternityRiskLevel;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PostnatalObservationStatus;
use App\Models\PostnatalCase;
use App\Models\PostnatalMotherObservation;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class PostnatalMotherObservationService
{
    public function __construct(
        private ActivityLogService $logger,
        private PostnatalRiskAssessmentService $riskAssessment,
    ) {}

    public function create(PostnatalCase $case, array $data, User $user): PostnatalMotherObservation
    {
        return DB::transaction(function () use ($case, $data, $user) {
            $data = $this->normalise($case, $data);
            $assessment = $this->riskAssessment->assessMother($data);
            $data['danger_signs'] = $assessment['danger_signs'];
            $data['risk_flags'] = $assessment['risk_flags'];
            $data['status'] = $assessment['requires_review'] ? PostnatalObservationStatus::REVIEW_REQUIRED : PostnatalObservationStatus::RECORDED;
            $data['observed_by'] = $data['observed_by'] ?? $user->id;
            $data['updated_by'] = $user->id;

            $observation = PostnatalMotherObservation::create($data);
            $this->syncCaseRisk($case, $assessment, $user);
            $this->log($observation->fresh($this->relations()), 'POSTNATAL_MOTHER_OBSERVATION_RECORDED', $user, $assessment);

            return $observation->fresh($this->relations());
        });
    }

    public function update(PostnatalMotherObservation $observation, array $data, User $user): PostnatalMotherObservation
    {
        return DB::transaction(function () use ($observation, $data, $user) {
            $data = $this->normalise($observation->postnatalCase, $data + $observation->getAttributes());
            $assessment = $this->riskAssessment->assessMother($data);
            $data['danger_signs'] = $assessment['danger_signs'];
            $data['risk_flags'] = $assessment['risk_flags'];
            $data['status'] = $assessment['requires_review'] ? PostnatalObservationStatus::REVIEW_REQUIRED : PostnatalObservationStatus::RECORDED;
            $data['updated_by'] = $user->id;

            $observation->update($data);
            $this->syncCaseRisk($observation->postnatalCase, $assessment, $user);
            $this->log($observation->fresh($this->relations()), 'POSTNATAL_MOTHER_OBSERVATION_UPDATED', $user, $assessment);

            return $observation->fresh($this->relations());
        });
    }

    public function cancel(PostnatalMotherObservation $observation, ?string $reason, User $user): PostnatalMotherObservation
    {
        $observation->update([
            'status' => PostnatalObservationStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        $this->log($observation->fresh($this->relations()), 'POSTNATAL_MOTHER_OBSERVATION_CANCELLED', $user);

        return $observation->fresh($this->relations());
    }

    public function relations(): array
    {
        return ['postnatalCase', 'deliveryRecord', 'pregnancyProfile', 'mother', 'visit', 'admission', 'department', 'observedBy'];
    }

    private function normalise(PostnatalCase $case, array $data): array
    {
        $data['postnatal_case_id'] = $case->id;
        $data['delivery_record_id'] = $case->delivery_record_id;
        $data['pregnancy_profile_id'] = $case->pregnancy_profile_id;
        $data['mother_patient_id'] = $case->mother_patient_id;
        $data['visit_id'] = $case->visit_id;
        $data['admission_id'] = $case->admission_id;
        $data['department_id'] = $case->department_id;
        $data['observed_at'] = $data['observed_at'] ?? now();
        $data['danger_signs'] = $this->values($data['danger_signs'] ?? []);
        $data['risk_flags'] = $this->values($data['risk_flags'] ?? []);

        foreach (['bleeding_status', 'uterus_condition', 'wound_condition', 'breastfeeding_status'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
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

    private function log(PostnatalMotherObservation $observation, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $observation->toActivityContext() + [
            'metadata' => [
                'postnatal_case_id' => $observation->postnatal_case_id,
                'mother_observation_id' => $observation->id,
                'status' => $observation->status?->value,
                'risk_flags_count' => count($observation->risk_flags ?? []),
                'danger_signs_count' => count($observation->danger_signs ?? []),
                'risk_level' => $extra['risk_level']?->value ?? null,
            ],
            'causer' => $user,
        ], $observation, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
