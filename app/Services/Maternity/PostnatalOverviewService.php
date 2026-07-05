<?php

namespace App\Services\Maternity;

use App\Enums\NewbornOutcome;
use App\Enums\PostnatalCaseStatus;
use App\Enums\PostnatalObservationStatus;
use App\Models\DeliveryRecord;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PostnatalMotherObservation;
use App\Models\PostnatalNewbornObservation;
use App\Models\PregnancyProfile;

class PostnatalOverviewService
{
    public function forDelivery(DeliveryRecord $delivery): array
    {
        $case = $delivery->postnatalCase()
            ->with(['latestMotherObservation', 'latestNewbornObservation', 'newbornObservations.newbornRecord', 'deliveryRecord.newbornRecords'])
            ->first();

        return $this->summary($case, $delivery);
    }

    public function forCase(PostnatalCase $case): array
    {
        $case->loadMissing(['deliveryRecord.newbornRecords.latestPostnatalObservation', 'latestMotherObservation', 'latestNewbornObservation', 'motherObservations.observedBy', 'newbornObservations.newbornRecord', 'admission.bed.ward']);

        return $this->summary($case, $case->deliveryRecord);
    }

    public function forProfile(PregnancyProfile $profile): array
    {
        $cases = $profile->postnatalCases()
            ->with(['deliveryRecord.newbornRecords', 'latestMotherObservation', 'latestNewbornObservation'])
            ->get();

        return [
            'cases' => $cases,
            'latest_case' => $cases->first(),
            'active_count' => $cases->filter(fn ($case) => ! $case->status?->isClosed())->count(),
            'latest_mother_observation' => $profile->postnatalMotherObservations()->first(),
            'latest_newborn_observation' => $profile->postnatalNewbornObservations()->first(),
            'danger_signs_count' => PostnatalMotherObservation::where('pregnancy_profile_id', $profile->id)->whereNotNull('danger_signs')->get()->sum(fn ($obs) => count($obs->danger_signs ?? []))
                + PostnatalNewbornObservation::where('pregnancy_profile_id', $profile->id)->whereNotNull('danger_signs')->get()->sum(fn ($obs) => count($obs->danger_signs ?? [])),
        ];
    }

    public function forNewborn(NewbornRecord $newborn): array
    {
        $case = $newborn->deliveryRecord?->postnatalCase;
        $latest = $newborn->postnatalNewbornObservations()->first();

        return [
            'case' => $case,
            'latest_observation' => $latest,
            'observation_count' => $newborn->postnatalNewbornObservations()->count(),
            'ready' => $case?->newbornReady() ?? false,
            'observation_required' => $newborn->outcome !== NewbornOutcome::STILLBIRTH,
        ];
    }

    public function dashboard(): array
    {
        return [
            'active_postnatal_cases' => PostnatalCase::active()->count(),
            'mother_observations_today' => PostnatalMotherObservation::whereDate('observed_at', today())->count(),
            'newborn_observations_today' => PostnatalNewbornObservation::whereDate('observed_at', today())->count(),
            'mothers_ready_for_discharge' => PostnatalCase::whereNotNull('mother_ready_at')->count(),
            'newborns_ready_for_discharge' => PostnatalCase::whereNotNull('newborn_ready_at')->count(),
            'postnatal_danger_signs_flagged' => PostnatalMotherObservation::where('status', PostnatalObservationStatus::REVIEW_REQUIRED->value)->count()
                + PostnatalNewbornObservation::where('status', PostnatalObservationStatus::REVIEW_REQUIRED->value)->count(),
            'postnatal_referrals_required' => PostnatalCase::where('referral_required', true)->active()->count(),
            'postnatal_followups_due_this_week' => PostnatalCase::whereBetween('follow_up_date', [today(), today()->endOfWeek()])->count(),
            'recent_postnatal_cases' => PostnatalCase::with(['mother', 'deliveryRecord', 'latestMotherObservation', 'latestNewbornObservation'])
                ->latest('opened_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    private function summary(?PostnatalCase $case, ?DeliveryRecord $delivery): array
    {
        $newbornRecords = $delivery?->newbornRecords()->with('latestPostnatalObservation')->get() ?? collect();
        $newbornsRequiringObservation = $newbornRecords->reject(fn ($record) => $record->outcome === NewbornOutcome::STILLBIRTH);
        $latestNewbornObservations = $case
            ? $case->newbornObservations()->with('newbornRecord')->limit(20)->get()
            : collect();

        return [
            'case' => $case,
            'newborn_records' => $newbornRecords,
            'live_newborn_count' => $newbornsRequiringObservation->count(),
            'newborn_observation_required' => $newbornsRequiringObservation->isNotEmpty(),
            'mother_ready' => $case?->motherReady() ?? false,
            'newborn_ready' => $case?->newbornReady() ?? false,
            'ready_for_discharge' => $case?->readyForDischarge() ?? false,
            'latest_mother_observation' => $case?->latestMotherObservation,
            'latest_newborn_observation' => $case?->latestNewbornObservation,
            'newborn_observations' => $latestNewbornObservations,
            'danger_signs_count' => ($case?->motherObservations()->get()->sum(fn ($obs) => count($obs->danger_signs ?? [])) ?? 0)
                + ($case?->newbornObservations()->get()->sum(fn ($obs) => count($obs->danger_signs ?? [])) ?? 0),
            'billing_placeholders' => [
                __('maternity.postnatal_mother_care_billing_placeholder'),
                __('maternity.postnatal_newborn_care_billing_placeholder'),
                __('maternity.neonatal_observation_billing_placeholder'),
                __('maternity.immunisation_billing_placeholder'),
                __('maternity.newborn_consumables_billing_placeholder'),
            ],
        ];
    }
}
