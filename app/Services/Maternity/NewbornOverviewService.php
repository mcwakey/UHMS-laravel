<?php

namespace App\Services\Maternity;

use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Models\DeliveryRecord;
use App\Models\NewbornRecord;
use App\Models\PregnancyProfile;

class NewbornOverviewService
{
    public function forDelivery(DeliveryRecord $delivery): array
    {
        $records = $delivery->newbornRecords()->with(['newbornPatient', 'recordedBy'])->get();
        $expected = max(0, (int) ($delivery->newborn_count ?? 0));
        $recorded = $records->count();

        return [
            'expected_count' => $expected,
            'recorded_count' => $recorded,
            'missing_count' => max(0, $expected - $recorded),
            'extra_count' => max(0, $recorded - $expected),
            'complete' => $delivery->newbornRecordsComplete(),
            'records' => $records,
            'outcome_summary' => $records->groupBy(fn ($record) => $record->outcome?->value ?? 'unknown')->map->count()->all(),
            'low_birth_weight_count' => $records->filter(fn ($record) => $record->birth_weight_kg !== null && (float) $record->birth_weight_kg < 2.5)->count(),
            'resuscitation_count' => $records->where('resuscitation_required', true)->count(),
            'at_risk_count' => $records->whereIn('status', [NewbornRecordStatus::AT_RISK, NewbornRecordStatus::UNDER_OBSERVATION])->count(),
        ];
    }

    public function forProfile(PregnancyProfile $profile): array
    {
        $records = $profile->newbornRecords()->with(['deliveryRecord', 'newbornPatient'])->get();

        return [
            'records' => $records,
            'recorded_count' => $records->count(),
            'pending_deliveries' => $profile->deliveryRecords()->where('newborn_records_pending', true)->count(),
            'live_births' => $records->filter(fn ($record) => $record->outcome === NewbornOutcome::LIVE_BIRTH)->count(),
            'stillbirths' => $records->filter(fn ($record) => $record->outcome === NewbornOutcome::STILLBIRTH)->count(),
            'newborn_warnings' => $this->warnings($records),
        ];
    }

    public function dashboard(): array
    {
        return [
            'newborn_records_pending' => DeliveryRecord::where('newborn_records_pending', true)->count(),
            'newborns_recorded_today' => NewbornRecord::whereDate('created_at', today())->count(),
            'live_births_today' => NewbornRecord::whereDate('birth_time', today())->where('outcome', NewbornOutcome::LIVE_BIRTH->value)->count(),
            'stillbirths_today' => NewbornRecord::whereDate('birth_time', today())->where('outcome', NewbornOutcome::STILLBIRTH->value)->count(),
            'newborns_under_observation' => NewbornRecord::where('status', NewbornRecordStatus::UNDER_OBSERVATION->value)->count(),
            'newborns_at_risk' => NewbornRecord::where('status', NewbornRecordStatus::AT_RISK->value)->count(),
            'resuscitation_required_today' => NewbornRecord::whereDate('birth_time', today())->where('resuscitation_required', true)->count(),
            'low_birth_weight_count' => NewbornRecord::whereNotNull('birth_weight_kg')->where('birth_weight_kg', '<', 2.5)->count(),
            'multiple_births_recorded' => DeliveryRecord::where('newborn_count', '>', 1)->whereHas('newbornRecords')->count(),
            'recent_newborn_records' => NewbornRecord::with(['mother', 'deliveryRecord', 'newbornPatient'])
                ->latest('birth_time')
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    private function warnings($records): array
    {
        $warnings = [];
        if ($records->where('status', NewbornRecordStatus::AT_RISK)->isNotEmpty()) {
            $warnings[] = __('maternity.newborns_at_risk');
        }
        if ($records->where('resuscitation_required', true)->isNotEmpty()) {
            $warnings[] = __('maternity.resuscitation_required');
        }

        return $warnings;
    }
}
