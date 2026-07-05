<?php

namespace App\Services\Maternity;

use App\Enums\AntenatalReferralType;
use App\Enums\AntenatalVisitStatus;
use App\Enums\PregnancyProfileStatus;
use App\Models\AntenatalVisit;
use App\Models\PregnancyProfile;

class AntenatalOverviewService
{
    public function forProfile(PregnancyProfile $profile): array
    {
        $latest = $profile->antenatalVisits()->with('recordedBy')->latest('visit_date')->first();
        $nextDate = $profile->antenatalVisits()
            ->whereNotNull('next_visit_date')
            ->latest('next_visit_date')
            ->value('next_visit_date');

        $nextDate = $nextDate ? now()->parse($nextDate)->startOfDay() : null;

        return [
            'visit_count' => $profile->antenatalVisits()->count(),
            'latest_visit' => $latest,
            'next_visit_date' => $nextDate,
            'missed_visit' => $nextDate ? $nextDate->lt(today()) : false,
            'high_risk' => $profile->profile_status === PregnancyProfileStatus::HIGH_RISK
                || ($latest && in_array($latest->status, [AntenatalVisitStatus::HIGH_RISK, AntenatalVisitStatus::REFERRED], true)),
            'danger_signs' => collect($latest?->danger_signs ?? [])->values(),
            'risk_flags' => collect($latest?->risk_flags ?? [])->values(),
            'pending_referral' => $latest && $latest->referral_type && $latest->referral_type !== AntenatalReferralType::NONE,
            'profile_warnings' => $this->profileWarnings($profile),
        ];
    }

    public function dashboard(): array
    {
        return [
            'anc_today' => AntenatalVisit::whereDate('visit_date', today())->count(),
            'anc_this_week' => AntenatalVisit::whereBetween('visit_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'missed_anc' => AntenatalVisit::whereNotNull('next_visit_date')->whereDate('next_visit_date', '<', today())->count(),
            'danger_signs_flagged' => AntenatalVisit::whereNotNull('danger_signs')->get()->filter(fn ($visit) => count($visit->danger_signs ?? []) > 0)->count(),
            'referrals_pending' => AntenatalVisit::whereNotNull('referral_type')->where('referral_type', '!=', AntenatalReferralType::NONE->value)->count(),
            'profiles_without_anc' => PregnancyProfile::active()->whereDoesntHave('antenatalVisits')->count(),
            'recent_anc_visits' => AntenatalVisit::with(['patient', 'pregnancyProfile', 'recordedBy'])
                ->latest('visit_date')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    private function profileWarnings(PregnancyProfile $profile): array
    {
        $warnings = [];
        if (! $profile->estimated_due_date && ! $profile->last_menstrual_period) {
            $warnings[] = __('maternity.warning_missing_dates');
        }
        if (! $profile->antenatalVisits()->exists()) {
            $warnings[] = __('maternity.no_anc_visits_yet');
        }

        return $warnings;
    }
}
