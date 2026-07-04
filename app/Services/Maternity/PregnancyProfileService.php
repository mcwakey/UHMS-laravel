<?php

namespace App\Services\Maternity;

use App\Enums\LogModule;
use App\Enums\MaternityRiskLevel;
use App\Enums\PregnancyProfileStatus;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;

class PregnancyProfileService
{
    public function __construct(private ActivityLogService $logger) {}

    public function create(array $data, User $user): PregnancyProfile
    {
        $data = $this->normalise($data);
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;
        $data['profile_status'] ??= PregnancyProfileStatus::ACTIVE->value;

        $profile = PregnancyProfile::create($data);
        $this->log($profile, 'PREGNANCY_PROFILE_CREATED', $user);

        return $profile->fresh(['patient', 'visit', 'admission', 'department']);
    }

    public function update(PregnancyProfile $profile, array $data, User $user): PregnancyProfile
    {
        $data = $this->normalise($data);
        $data['updated_by'] = $user->id;
        $profile->update($data);
        $this->log($profile, 'PREGNANCY_PROFILE_UPDATED', $user);

        return $profile->fresh(['patient', 'visit', 'admission', 'department']);
    }

    public function markHighRisk(PregnancyProfile $profile, ?string $reason, User $user): PregnancyProfile
    {
        $risks = collect($profile->known_risks ?? []);
        if ($reason) {
            $risks->push($reason);
        }

        $profile->update([
            'profile_status' => PregnancyProfileStatus::HIGH_RISK,
            'known_risks' => $risks->filter()->unique()->values()->all(),
            'updated_by' => $user->id,
        ]);

        $this->log($profile, 'PREGNANCY_PROFILE_MARKED_HIGH_RISK', $user, [
            'metadata' => ['risk_count' => count($profile->known_risks ?? [])],
        ]);

        return $profile->fresh(['patient', 'visit', 'admission', 'department']);
    }

    public function close(PregnancyProfile $profile, PregnancyProfileStatus $status, ?string $reason, User $user): PregnancyProfile
    {
        $profile->update([
            'profile_status' => $status,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closure_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        $this->log($profile, 'PREGNANCY_PROFILE_CLOSED', $user, [
            'metadata' => ['status' => $status->value, 'has_reason' => filled($reason)],
        ]);

        return $profile->fresh(['patient', 'visit', 'admission', 'department']);
    }

    private function normalise(array $data): array
    {
        foreach (['previous_caesarean', 'previous_postpartum_haemorrhage', 'hypertensive_disorder_risk', 'diabetes_risk', 'multiple_pregnancy'] as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        if (! empty($data['known_risks']) && is_string($data['known_risks'])) {
            $data['known_risks'] = collect(preg_split('/\r\n|\r|\n/', $data['known_risks']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
        }

        if (! empty($data['last_menstrual_period'])) {
            $lmp = Carbon::parse($data['last_menstrual_period'])->startOfDay();
            $data['estimated_due_date'] = $data['estimated_due_date'] ?? $lmp->copy()->addDays(280)->toDateString();

            if ($lmp->lte(today())) {
                $days = (int) $lmp->diffInDays(today());
                $data['gestational_age_weeks'] = intdiv($days, 7);
                $data['gestational_age_days'] = $days % 7;
            }
        }

        return $data;
    }

    private function log(PregnancyProfile $profile, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $profile->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'profile_id' => $profile->id,
                'profile_status' => $profile->profile_status?->value,
            ]),
            'causer' => $user,
        ], $profile, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
