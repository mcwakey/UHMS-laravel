<?php

namespace App\Services\Maternity;

use App\Enums\LogModule;
use App\Enums\MaternityRiskLevel;
use App\Enums\NewbornOutcome;
use App\Enums\PostnatalCaseStatus;
use App\Models\DeliveryRecord;
use App\Models\PostnatalCase;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class PostnatalCaseService
{
    public function __construct(private ActivityLogService $logger) {}

    public function openFromDelivery(DeliveryRecord $delivery, array $data, User $user): PostnatalCase
    {
        return DB::transaction(function () use ($delivery, $data, $user) {
            $existing = $delivery->postnatalCases()
                ->whereNotIn('status', [PostnatalCaseStatus::CLOSED->value, PostnatalCaseStatus::CANCELLED->value])
                ->first();

            if ($existing) {
                return $existing->fresh($this->relations());
            }

            $case = PostnatalCase::create([
                'delivery_record_id' => $delivery->id,
                'labor_episode_id' => $delivery->labor_episode_id,
                'pregnancy_profile_id' => $delivery->pregnancy_profile_id,
                'maternity_case_id' => $delivery->maternity_case_id,
                'mother_patient_id' => $delivery->patient_id,
                'visit_id' => $delivery->visit_id,
                'admission_id' => $delivery->admission_id,
                'department_id' => $delivery->department_id,
                'opened_by' => $user->id,
                'updated_by' => $user->id,
                'opened_at' => now(),
                'newborn_ready_at' => $this->requiresNewbornReadiness($delivery) ? null : now(),
                'newborn_ready_by' => $this->requiresNewbornReadiness($delivery) ? null : $user->id,
                'status' => PostnatalCaseStatus::OPEN,
                'risk_level' => $data['risk_level'] ?? MaternityRiskLevel::LOW->value,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'follow_up_instructions' => $data['follow_up_instructions'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->log($case->fresh($this->relations()), 'POSTNATAL_CASE_OPENED', $user);

            return $case->fresh($this->relations());
        });
    }

    public function update(PostnatalCase $case, array $data, User $user): PostnatalCase
    {
        $updates = collect($data)->only([
            'risk_level',
            'follow_up_date',
            'follow_up_instructions',
            'notes',
            'referral_required',
            'referral_reason',
        ])->all();
        $updates['updated_by'] = $user->id;

        if (array_key_exists('referral_required', $updates) && $updates['referral_required']) {
            $updates['referral_marked_at'] = $case->referral_marked_at ?: now();
            $updates['referral_marked_by'] = $case->referral_marked_by ?: $user->id;
        }

        $case->update($updates);
        $this->log($case->fresh($this->relations()), 'POSTNATAL_CASE_UPDATED', $user);

        return $case->fresh($this->relations());
    }

    public function markMotherReady(PostnatalCase $case, User $user): PostnatalCase
    {
        $case->update([
            'mother_ready_at' => $case->mother_ready_at ?: now(),
            'mother_ready_by' => $case->mother_ready_by ?: $user->id,
            'status' => $case->newbornReady() ? PostnatalCaseStatus::READY_FOR_DISCHARGE : PostnatalCaseStatus::MOTHER_READY,
            'updated_by' => $user->id,
        ]);

        $this->log($case->fresh($this->relations()), 'POSTNATAL_MOTHER_MARKED_READY', $user);

        return $case->fresh($this->relations());
    }

    public function markNewbornReady(PostnatalCase $case, User $user): PostnatalCase
    {
        $case->update([
            'newborn_ready_at' => $case->newborn_ready_at ?: now(),
            'newborn_ready_by' => $case->newborn_ready_by ?: $user->id,
            'status' => $case->motherReady() ? PostnatalCaseStatus::READY_FOR_DISCHARGE : PostnatalCaseStatus::NEWBORN_READY,
            'updated_by' => $user->id,
        ]);

        $this->log($case->fresh($this->relations()), 'POSTNATAL_NEWBORN_MARKED_READY', $user);

        return $case->fresh($this->relations());
    }

    public function markReadyForDischarge(PostnatalCase $case, User $user): PostnatalCase
    {
        $case->update([
            'ready_for_discharge_at' => $case->ready_for_discharge_at ?: now(),
            'ready_for_discharge_by' => $case->ready_for_discharge_by ?: $user->id,
            'mother_ready_at' => $case->mother_ready_at ?: now(),
            'mother_ready_by' => $case->mother_ready_by ?: $user->id,
            'newborn_ready_at' => $case->newborn_ready_at ?: now(),
            'newborn_ready_by' => $case->newborn_ready_by ?: $user->id,
            'status' => PostnatalCaseStatus::READY_FOR_DISCHARGE,
            'updated_by' => $user->id,
        ]);

        $this->log($case->fresh($this->relations()), 'POSTNATAL_READY_FOR_DISCHARGE', $user);

        return $case->fresh($this->relations());
    }

    public function markReferralRequired(PostnatalCase $case, ?string $reason, User $user): PostnatalCase
    {
        $case->update([
            'referral_required' => true,
            'referral_reason' => $reason ?: $case->referral_reason,
            'referral_marked_at' => now(),
            'referral_marked_by' => $user->id,
            'status' => PostnatalCaseStatus::REFERRAL_REQUIRED,
            'updated_by' => $user->id,
        ]);

        $this->log($case->fresh($this->relations()), 'POSTNATAL_REFERRAL_REQUIRED', $user);

        return $case->fresh($this->relations());
    }

    public function close(PostnatalCase $case, PostnatalCaseStatus $status, ?string $reason, User $user): PostnatalCase
    {
        $case->update([
            'status' => $status,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closure_reason' => $reason ?: $case->closure_reason,
            'updated_by' => $user->id,
        ]);

        $this->log($case->fresh($this->relations()), $status === PostnatalCaseStatus::CANCELLED ? 'POSTNATAL_CASE_CANCELLED' : 'POSTNATAL_CASE_CLOSED', $user);

        return $case->fresh($this->relations());
    }

    public function relations(): array
    {
        return ['deliveryRecord.newbornRecords', 'laborEpisode', 'pregnancyProfile', 'maternityCase', 'mother', 'visit', 'admission.bed.ward', 'department', 'latestMotherObservation', 'latestNewbornObservation'];
    }

    private function requiresNewbornReadiness(DeliveryRecord $delivery): bool
    {
        $records = $delivery->newbornRecords()->get(['outcome']);

        if ($records->isEmpty()) {
            return $delivery->newbornCountExpected() > 0;
        }

        return $records->contains(fn ($record) => $record->outcome !== NewbornOutcome::STILLBIRTH);
    }

    private function log(PostnatalCase $case, string $action, User $user): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $case->toActivityContext() + [
            'metadata' => [
                'postnatal_case_id' => $case->id,
                'delivery_record_id' => $case->delivery_record_id,
                'pregnancy_profile_id' => $case->pregnancy_profile_id,
                'status' => $case->status?->value,
                'risk_level' => $case->risk_level?->value,
                'referral_required' => $case->referral_required,
                'mother_ready' => $case->motherReady(),
                'newborn_ready' => $case->newbornReady(),
            ],
            'causer' => $user,
        ], $case, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
