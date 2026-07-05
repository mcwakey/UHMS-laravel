<?php

namespace App\Services\Maternity;

use App\Enums\DeliveryRecordStatus;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborObservationStatus;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\LaborObservation;
use App\Models\PregnancyProfile;

class LaborOverviewService
{
    public function forProfile(PregnancyProfile $profile): array
    {
        $active = $profile->laborEpisodes()->open()->with(['latestObservation', 'latestDeliveryRecord', 'admission.bed.ward'])->first();
        $latest = $profile->laborEpisodes()->with(['latestObservation', 'latestDeliveryRecord'])->first();

        return [
            'episode_count' => $profile->laborEpisodes()->count(),
            'active_episode' => $active,
            'latest_episode' => $latest,
            'latest_observation' => $latest?->latestObservation,
            'latest_delivery_record' => $latest?->latestDeliveryRecord,
            'newborn_records_pending' => $profile->deliveryRecords()->where('newborn_records_pending', true)->exists(),
            'warnings' => $this->warnings($active ?: $latest),
        ];
    }

    public function forEpisode(LaborEpisode $episode): array
    {
        $episode->loadMissing(['latestObservation', 'deliveryRecords.newbornRecords', 'admission.bed.ward']);
        $delivery = $episode->deliveryRecords->first();

        return [
            'latest_observation' => $episode->latestObservation,
            'delivery_record' => $delivery,
            'newborn_records' => $delivery?->newbornRecords ?? collect(),
            'warnings' => $this->warnings($episode),
            'admission' => $episode->admission,
            'partograph_ready' => $episode->observations()->exists(),
        ];
    }

    public function dashboard(): array
    {
        return [
            'active_labor_episodes' => LaborEpisode::open()->count(),
            'labor_cases_by_stage' => LaborEpisode::open()
                ->selectRaw('labor_stage, count(*) as total')
                ->groupBy('labor_stage')
                ->pluck('total', 'labor_stage')
                ->all(),
            'labor_danger_signs_flagged' => LaborObservation::whereNotNull('danger_signs')->get()->filter(fn ($observation) => count($observation->danger_signs ?? []) > 0)->count(),
            'theatre_escalation_required' => LaborEpisode::open()->where('theatre_escalation_required', true)->count(),
            'emergency_escalation_required' => LaborEpisode::open()->where('emergency_escalation_required', true)->count(),
            'deliveries_today' => DeliveryRecord::whereDate('delivery_at', today())->count(),
            'delivery_records_pending_newborn' => DeliveryRecord::where('newborn_records_pending', true)
                ->whereIn('status', [DeliveryRecordStatus::RECORDED->value, DeliveryRecordStatus::COMPLETED->value])
                ->count(),
            'recent_labor_observations' => LaborObservation::with(['laborEpisode', 'patient', 'recordedBy'])
                ->latest('observed_at')
                ->latest('id')
                ->limit(8)
                ->get(),
            'active_labor_list' => LaborEpisode::with(['patient', 'pregnancyProfile', 'admission.bed.ward', 'latestObservation', 'latestDeliveryRecord'])
                ->open()
                ->latest('started_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    private function warnings(?LaborEpisode $episode): array
    {
        if (! $episode) {
            return [];
        }

        $warnings = [];
        if ($episode->theatre_escalation_required) {
            $warnings[] = __('maternity.theatre_escalation_required');
        }
        if ($episode->emergency_escalation_required) {
            $warnings[] = __('maternity.emergency_escalation_required');
        }
        if ($episode->latestObservation && $episode->latestObservation->status === LaborObservationStatus::ESCALATED) {
            $warnings[] = __('maternity.labor_observation_escalated');
        }
        if ($episode->status === LaborEpisodeStatus::DELIVERY_PENDING) {
            $warnings[] = __('maternity.delivery_record_pending_completion');
        }

        return array_values(array_unique($warnings));
    }
}
