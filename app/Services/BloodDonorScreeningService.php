<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\BloodDonor;
use App\Models\BloodDonorScreening;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * WHO-style donor screening: questionnaire → physical assessment → eligibility
 * decision (with deferral) → consent. Eligibility is suggested from configurable
 * thresholds and questionnaire risk answers, but a permitted user may override.
 */
class BloodDonorScreeningService
{
    public function __construct(private ActivityLogService $log) {}

    /** Get the donor's open screening or start a new one. */
    public function startScreening(BloodDonor $donor, User $user): BloodDonorScreening
    {
        $screening = $donor->screenings()
            ->whereNull('donation_id')
            ->whereNotIn('stage', [
                BloodDonorScreening::STAGE_ELIGIBLE,
                BloodDonorScreening::STAGE_TEMP_DEFERRED,
                BloodDonorScreening::STAGE_PERM_DEFERRED,
            ])
            ->latest()
            ->first();

        if ($screening) {
            return $screening;
        }

        $screening = $donor->screenings()->create([
            'stage' => BloodDonorScreening::STAGE_QUESTIONNAIRE_PENDING,
            'created_by' => $user->id,
        ]);

        if ($donor->screening_status === BloodDonor::SCREENING_REGISTERED || $donor->screening_status === null) {
            $donor->update(['screening_status' => BloodDonor::SCREENING_QUESTIONNAIRE_PENDING]);
        }

        return $screening;
    }

    public function recordQuestionnaire(BloodDonorScreening $screening, array $answers, User $user, array $consent = []): BloodDonorScreening
    {
        return DB::transaction(function () use ($screening, $answers, $user, $consent) {
            $screening->update([
                'questionnaire' => $answers,
                'questionnaire_by' => $user->id,
                'questionnaire_at' => now(),
                'stage' => BloodDonorScreening::STAGE_PHYSICAL_PENDING,
                'consent_donate' => (bool) ($consent['consent_donate'] ?? $screening->consent_donate),
                'consent_testing' => (bool) ($consent['consent_testing'] ?? $screening->consent_testing),
                'consent_contact' => (bool) ($consent['consent_contact'] ?? $screening->consent_contact),
            ]);

            $screening->donor->update(['screening_status' => BloodDonor::SCREENING_PHYSICAL_PENDING]);

            $this->log->log(LogModule::BLOOD_BANK, 'DONOR_QUESTIONNAIRE_COMPLETED', [
                'description' => "Donor questionnaire completed for {$screening->donor->donor_number}",
                'causer' => $user,
            ], $screening);

            return $screening->refresh();
        });
    }

    public function recordPhysicalAssessment(BloodDonorScreening $screening, array $data, User $user): BloodDonorScreening
    {
        return DB::transaction(function () use ($screening, $data, $user) {
            $screening->update(array_merge([
                'assessed_by' => $user->id,
                'assessed_at' => now(),
                'stage' => BloodDonorScreening::STAGE_PHYSICAL_COMPLETED,
            ], array_intersect_key($data, array_flip([
                'weight_kg', 'temperature_c', 'pulse', 'bp_systolic', 'bp_diastolic',
                'hemoglobin', 'general_appearance', 'venous_access', 'fitness_notes',
            ]))));

            $this->log->log(LogModule::BLOOD_BANK, 'DONOR_PHYSICAL_ASSESSMENT_COMPLETED', [
                'description' => "Physical assessment recorded for {$screening->donor->donor_number}",
                'causer' => $user,
            ], $screening);

            return $screening->refresh();
        });
    }

    /**
     * Calculate a suggested eligibility decision from thresholds + questionnaire.
     *
     * @return array{decision:string, flags:array<int,string>}
     */
    public function suggestEligibility(BloodDonorScreening $screening): array
    {
        $cfg = config('blood_bank.donor');
        $flags = [];
        $permanent = false;

        $donor = $screening->donor;
        $age = $donor->age;
        if ($age !== null && ($age < $cfg['minimum_age'] || $age > $cfg['maximum_age'])) {
            $flags[] = "Age {$age} outside accepted range ({$cfg['minimum_age']}–{$cfg['maximum_age']})";
        }

        if ($screening->weight_kg !== null && (float) $screening->weight_kg < $cfg['minimum_weight_kg']) {
            $flags[] = "Weight {$screening->weight_kg}kg below minimum {$cfg['minimum_weight_kg']}kg";
        }

        if ($screening->hemoglobin !== null) {
            $minHb = strtoupper((string) $donor->gender) === 'FEMALE' ? $cfg['minimum_hb_female'] : $cfg['minimum_hb_male'];
            if ((float) $screening->hemoglobin < $minHb) {
                $flags[] = "Hemoglobin {$screening->hemoglobin} below minimum {$minHb}";
            }
        }

        if ($screening->temperature_c !== null && (float) $screening->temperature_c > $cfg['maximum_temperature_c']) {
            $flags[] = "Temperature {$screening->temperature_c}°C above maximum {$cfg['maximum_temperature_c']}°C";
        }

        if ($screening->pulse !== null && ($screening->pulse < $cfg['pulse_min'] || $screening->pulse > $cfg['pulse_max'])) {
            $flags[] = "Pulse {$screening->pulse} outside range ({$cfg['pulse_min']}–{$cfg['pulse_max']})";
        }

        if ($screening->bp_systolic !== null && ($screening->bp_systolic < $cfg['systolic_min'] || $screening->bp_systolic > $cfg['systolic_max'])) {
            $flags[] = "Systolic BP {$screening->bp_systolic} outside range ({$cfg['systolic_min']}–{$cfg['systolic_max']})";
        }

        if ($screening->bp_diastolic !== null && ($screening->bp_diastolic < $cfg['diastolic_min'] || $screening->bp_diastolic > $cfg['diastolic_max'])) {
            $flags[] = "Diastolic BP {$screening->bp_diastolic} outside range ({$cfg['diastolic_min']}–{$cfg['diastolic_max']})";
        }

        // Donation interval
        if ($donor->last_donation_at && $donor->last_donation_at->diffInDays(now()) < $cfg['donation_interval_days']) {
            $flags[] = "Last donation within {$cfg['donation_interval_days']}-day interval";
        }

        // Questionnaire risk answers
        $answers = $screening->questionnaire ?? [];
        foreach ((array) config('blood_bank.questionnaire_risk.permanent') as $key) {
            if (! empty($answers[$key])) {
                $flags[] = 'Permanent risk: '.$this->humanise($key);
                $permanent = true;
            }
        }
        foreach ((array) config('blood_bank.questionnaire_risk.temporary') as $key) {
            if (! empty($answers[$key])) {
                $flags[] = 'Temporary risk: '.$this->humanise($key);
            }
        }

        if ($permanent) {
            $decision = BloodDonorScreening::DECISION_PERM_DEFERRED;
        } elseif (! empty($flags)) {
            $decision = BloodDonorScreening::DECISION_TEMP_DEFERRED;
        } else {
            $decision = BloodDonorScreening::DECISION_ELIGIBLE;
        }

        return ['decision' => $decision, 'flags' => $flags];
    }

    /**
     * Finalise the eligibility decision. Pass $decision to override the
     * automatic suggestion (requires $override = true + reason + permission).
     */
    public function decideEligibility(
        BloodDonorScreening $screening,
        User $user,
        ?string $decision = null,
        array $data = [],
        bool $override = false
    ): BloodDonorScreening {
        $suggestion = $this->suggestEligibility($screening);
        $finalDecision = $decision ?: $suggestion['decision'];

        // Overriding a deferral suggestion to ELIGIBLE requires explicit reason.
        $isOverride = $override
            && $finalDecision === BloodDonorScreening::DECISION_ELIGIBLE
            && $suggestion['decision'] !== BloodDonorScreening::DECISION_ELIGIBLE;

        if ($isOverride && empty($data['override_reason'])) {
            throw ValidationException::withMessages([
                'override_reason' => 'A reason is required to override the donor eligibility suggestion.',
            ]);
        }

        if (in_array($finalDecision, [BloodDonorScreening::DECISION_TEMP_DEFERRED, BloodDonorScreening::DECISION_PERM_DEFERRED], true)
            && empty($data['deferral_reason']) && empty($suggestion['flags'])) {
            throw ValidationException::withMessages([
                'deferral_reason' => 'A deferral reason is required.',
            ]);
        }

        return DB::transaction(function () use ($screening, $user, $finalDecision, $suggestion, $data, $isOverride) {
            $deferralType = match ($finalDecision) {
                BloodDonorScreening::DECISION_TEMP_DEFERRED => 'TEMPORARY',
                BloodDonorScreening::DECISION_PERM_DEFERRED => 'PERMANENT',
                default => null,
            };

            $stage = match ($finalDecision) {
                BloodDonorScreening::DECISION_ELIGIBLE => BloodDonorScreening::STAGE_ELIGIBLE,
                BloodDonorScreening::DECISION_TEMP_DEFERRED => BloodDonorScreening::STAGE_TEMP_DEFERRED,
                BloodDonorScreening::DECISION_PERM_DEFERRED => BloodDonorScreening::STAGE_PERM_DEFERRED,
                default => BloodDonorScreening::STAGE_PHYSICAL_COMPLETED,
            };

            $screening->update([
                'eligibility_decision' => $finalDecision,
                'suggested_flags' => $suggestion['flags'],
                'deferral_type' => $deferralType,
                'deferral_reason' => $deferralType ? ($data['deferral_reason'] ?? implode('; ', $suggestion['flags'])) : null,
                'deferral_until' => $deferralType === 'TEMPORARY' ? ($data['deferral_until'] ?? null) : null,
                'eligibility_overridden' => $isOverride,
                'override_reason' => $isOverride ? $data['override_reason'] : null,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'stage' => $stage,
                'notes' => $data['notes'] ?? $screening->notes,
            ]);

            $donorScreeningStatus = match ($finalDecision) {
                BloodDonorScreening::DECISION_ELIGIBLE => BloodDonor::SCREENING_ELIGIBLE,
                BloodDonorScreening::DECISION_TEMP_DEFERRED => BloodDonor::SCREENING_TEMP_DEFERRED,
                BloodDonorScreening::DECISION_PERM_DEFERRED => BloodDonor::SCREENING_PERM_DEFERRED,
                default => BloodDonor::SCREENING_PHYSICAL_PENDING,
            };

            $screening->donor->update([
                'screening_status' => $donorScreeningStatus,
                'status' => match ($finalDecision) {
                    BloodDonorScreening::DECISION_ELIGIBLE => BloodDonor::STATUS_ACTIVE,
                    BloodDonorScreening::DECISION_TEMP_DEFERRED, BloodDonorScreening::DECISION_PERM_DEFERRED => BloodDonor::STATUS_DEFERRED,
                    default => $screening->donor->status,
                },
                'deferral_reason' => $deferralType ? ($data['deferral_reason'] ?? implode('; ', $suggestion['flags'])) : null,
                'deferral_type' => $deferralType,
                'deferred_until' => $deferralType === 'TEMPORARY' ? ($data['deferral_until'] ?? null) : null,
            ]);

            $action = $deferralType ? 'DONOR_DEFERRED' : 'DONOR_ELIGIBILITY_DECIDED';
            $this->log->log(LogModule::BLOOD_BANK, $action, [
                'description' => "Donor {$screening->donor->donor_number} decision: {$finalDecision}".($isOverride ? ' (overridden)' : ''),
                'severity' => $isOverride ? \App\Enums\LogSeverity::WARNING : \App\Enums\LogSeverity::INFO,
                'causer' => $user,
                'metadata' => ['flags' => $suggestion['flags'], 'override' => $isOverride],
            ], $screening);

            return $screening->refresh();
        });
    }

    private function humanise(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }
}
