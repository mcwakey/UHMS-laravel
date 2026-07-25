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

    /**
     * Build chart-ready partograph series for a labor episode.
     *
     * The x-axis is elapsed hours since the first observation (the WHO
     * partograph plots progress against time). Each point also carries a
     * clock-time label (`t`) for the tooltip. The cervicograph carries the
     * WHO alert line (1 cm/hour from the first active-phase dilation ≥ 4 cm)
     * and the action line (4 hours to its right).
     */
    public function partograph(LaborEpisode $episode): array
    {
        // observations() defaults to newest-first; the partograph needs a
        // chronological series, so reset the ordering to ascending.
        $observations = $episode->observations()
            ->where('status', '!=', LaborObservationStatus::CANCELLED->value)
            ->reorder('observed_at', 'asc')
            ->orderBy('id')
            ->get();

        if ($observations->isEmpty()) {
            return ['has_data' => false];
        }

        $start = $observations->first()->observed_at;

        $hoursSince = fn ($when) => round(abs($start->diffInMinutes($when)) / 60, 2);

        $point = function (LaborObservation $observation, $value) use ($hoursSince) {
            if ($value === null || $value === '') {
                return null;
            }

            return [
                'x' => $hoursSince($observation->observed_at),
                'y' => (float) $value,
                't' => $observation->observed_at?->format('d M H:i'),
            ];
        };

        $series = [
            'dilation' => [],
            'descent' => [],
            'fhr' => [],
            'contractions' => [],
            'pulse' => [],
            'systolic' => [],
            'diastolic' => [],
            'temperature' => [],
        ];

        foreach ($observations as $observation) {
            if ($p = $point($observation, $observation->cervical_dilation_cm)) {
                $series['dilation'][] = $p;
            }
            if (($descent = $this->parseDescentFifths($observation->descent)) !== null
                && ($p = $point($observation, $descent))) {
                $series['descent'][] = $p;
            }
            if ($p = $point($observation, $observation->fetal_heart_rate)) {
                $series['fhr'][] = $p;
            }
            if ($p = $point($observation, $observation->contractions_per_10_min)) {
                $series['contractions'][] = $p;
            }
            if ($p = $point($observation, $observation->maternal_pulse)) {
                $series['pulse'][] = $p;
            }
            if ($p = $point($observation, $observation->blood_pressure_systolic)) {
                $series['systolic'][] = $p;
            }
            if ($p = $point($observation, $observation->blood_pressure_diastolic)) {
                $series['diastolic'][] = $p;
            }
            if ($p = $point($observation, $observation->temperature)) {
                $series['temperature'][] = $p;
            }
        }

        $maxHour = max(1, (int) ceil($hoursSince($observations->last()->observed_at)) + 1);

        return [
            'has_data' => true,
            'max_hour' => $maxHour,
            'started_at' => $start?->format('d M Y H:i'),
            'series' => $series,
        ] + $this->progressLines($observations, $hoursSince);
    }

    /**
     * WHO alert & action lines, anchored on the first active-phase
     * observation (cervical dilation ≥ 4 cm). Returns two-point lines in
     * the same hours-since-start units, or nulls when the active phase has
     * not been reached yet.
     *
     * @param  \Illuminate\Support\Collection<int, LaborObservation>  $observations
     * @return array{alert_line: ?array, action_line: ?array}
     */
    private function progressLines($observations, callable $hoursSince): array
    {
        $anchor = $observations->first(fn (LaborObservation $o) => $o->cervical_dilation_cm !== null && (float) $o->cervical_dilation_cm >= 4);

        if (! $anchor) {
            return ['alert_line' => null, 'action_line' => null];
        }

        $anchorHour = $hoursSince($anchor->observed_at);
        // From ≥4 cm it takes 6 hours to reach full dilation at 1 cm/hour.
        $alertStart = ['x' => round($anchorHour, 2), 'y' => 4];
        $alertEnd = ['x' => round($anchorHour + 6, 2), 'y' => 10];
        // Action line is the alert line shifted 4 hours to the right.
        $actionStart = ['x' => round($anchorHour + 4, 2), 'y' => 4];
        $actionEnd = ['x' => round($anchorHour + 10, 2), 'y' => 10];

        return [
            'alert_line' => [$alertStart, $alertEnd],
            'action_line' => [$actionStart, $actionEnd],
        ];
    }

    /**
     * Best-effort parse of the free-text descent field into fifths
     * palpable above the brim (0–5). Accepts "3/5", "3", "-3", etc.;
     * returns null when nothing numeric can be extracted.
     */
    private function parseDescentFifths(?string $descent): ?int
    {
        if ($descent === null || trim($descent) === '') {
            return null;
        }

        if (! preg_match('/-?\d+/', $descent, $matches)) {
            return null;
        }

        $value = abs((int) $matches[0]);

        return $value > 5 ? null : $value;
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
