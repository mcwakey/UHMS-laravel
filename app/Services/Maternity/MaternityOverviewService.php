<?php

namespace App\Services\Maternity;

use App\Enums\MaternityCaseStatus;
use App\Enums\PregnancyProfileStatus;
use App\Models\Admission;
use App\Models\MaternityCase;
use App\Models\PregnancyProfile;

class MaternityOverviewService
{
    public function dashboard(): array
    {
        return [
            'active_pregnancies' => PregnancyProfile::active()->count(),
            'high_risk_pregnancies' => PregnancyProfile::where('profile_status', PregnancyProfileStatus::HIGH_RISK->value)->count(),
            'open_cases' => MaternityCase::open()->count(),
            'maternity_admissions' => Admission::query()->whereHas('maternityCases', fn ($q) => $q->open())->count(),
            'expected_delivery_this_month' => PregnancyProfile::active()
                ->whereBetween('estimated_due_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'recent_cases' => MaternityCase::with(['patient', 'pregnancyProfile', 'admission'])
                ->latest('opened_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    public function forProfile(PregnancyProfile $profile): array
    {
        $profile->loadMissing(['patient.activeAdmission', 'visit', 'admission', 'department', 'maternityCases.openedBy']);
        $latestCase = $profile->maternityCases->sortByDesc('opened_at')->first();

        return [
            'active_profile' => $profile->profile_status && ! $profile->profile_status->isClosed(),
            'risk_summary' => $this->riskSummary($profile),
            'latest_case' => $latestCase,
            'admission' => $profile->admission ?: $profile->patient?->activeAdmission,
            'visit' => $profile->visit,
            'warnings' => $this->warnings($profile),
            'future_panels' => [
                __('maternity.future_anc_placeholder'),
                __('maternity.future_labor_placeholder'),
                __('maternity.future_delivery_placeholder'),
                __('maternity.future_newborn_placeholder'),
                __('maternity.future_postnatal_placeholder'),
            ],
        ];
    }

    private function riskSummary(PregnancyProfile $profile): array
    {
        return [
            'status' => $profile->profile_status,
            'flags' => collect([
                'previous_caesarean' => $profile->previous_caesarean,
                'previous_postpartum_haemorrhage' => $profile->previous_postpartum_haemorrhage,
                'hypertensive_disorder_risk' => $profile->hypertensive_disorder_risk,
                'diabetes_risk' => $profile->diabetes_risk,
                'multiple_pregnancy' => $profile->multiple_pregnancy,
            ])->filter()->keys()->values(),
            'known_risks' => collect($profile->known_risks ?? []),
        ];
    }

    private function warnings(PregnancyProfile $profile): array
    {
        $warnings = [];
        if (! $profile->last_menstrual_period && ! $profile->estimated_due_date) {
            $warnings[] = __('maternity.warning_missing_dates');
        }
        if (! $profile->blood_group || ! $profile->rhesus_status) {
            $warnings[] = __('maternity.warning_missing_blood_group');
        }
        if ($profile->profile_status === PregnancyProfileStatus::HIGH_RISK) {
            $warnings[] = __('maternity.warning_high_risk');
        }

        return $warnings;
    }
}
