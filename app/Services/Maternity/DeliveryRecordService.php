<?php

namespace App\Services\Maternity;

use App\Enums\DeliveryRecordStatus;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\LogModule;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;

class DeliveryRecordService
{
    public function __construct(private ActivityLogService $logger) {}

    public function create(LaborEpisode $episode, array $data, User $user): DeliveryRecord
    {
        $data = $this->normalise($episode, $data);
        $data['recorded_by'] = $data['recorded_by'] ?? $user->id;
        $data['status'] = $data['status'] ?? DeliveryRecordStatus::DRAFT->value;
        $data['newborn_count'] = $data['newborn_count'] ?? 1;
        $data['newborn_records_pending'] = $data['newborn_records_pending'] ?? true;

        $record = DeliveryRecord::create($data);
        $episode->update([
            'status' => LaborEpisodeStatus::DELIVERY_PENDING,
            'labor_stage' => LaborStage::THIRD_STAGE,
            'updated_by' => $user->id,
        ]);
        $this->log($record->fresh($this->relations()), 'DELIVERY_RECORD_CREATED', $user);

        return $record->fresh($this->relations());
    }

    public function update(DeliveryRecord $record, array $data, User $user): DeliveryRecord
    {
        $data = $this->normalise($record->laborEpisode, $data, $record);
        $record->update($data);
        $this->log($record->fresh($this->relations()), 'DELIVERY_RECORD_UPDATED', $user);

        return $record->fresh($this->relations());
    }

    public function complete(DeliveryRecord $record, array $data, User $user): DeliveryRecord
    {
        $data = $this->normalise($record->laborEpisode, $data, $record);
        $data['status'] = DeliveryRecordStatus::COMPLETED;
        $data['newborn_records_pending'] = true;
        $data['delivery_at'] = $data['delivery_at'] ?? $record->delivery_at ?? now();

        $record->update($data);
        $record->laborEpisode->update([
            'status' => LaborEpisodeStatus::DELIVERED,
            'labor_stage' => LaborStage::COMPLETED,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $this->log($record->fresh($this->relations()), 'DELIVERY_RECORD_COMPLETED', $user);

        return $record->fresh($this->relations());
    }

    public function relations(): array
    {
        return ['laborEpisode', 'pregnancyProfile', 'maternityCase', 'patient', 'visit', 'admission', 'department', 'recordedBy', 'attendingStaff'];
    }

    private function normalise(LaborEpisode $episode, array $data, ?DeliveryRecord $existing = null): array
    {
        $data['labor_episode_id'] = $episode->id;
        $data['pregnancy_profile_id'] = $episode->pregnancy_profile_id;
        $data['maternity_case_id'] = $data['maternity_case_id'] ?? $episode->maternity_case_id;
        $data['patient_id'] = $episode->patient_id;
        $data['visit_id'] = $data['visit_id'] ?? $episode->visit_id;
        $data['admission_id'] = $data['admission_id'] ?? $episode->admission_id;
        $data['department_id'] = $data['department_id'] ?? $episode->department_id;
        $data['complications'] = $this->arrayValues($data['complications'] ?? $existing?->complications ?? []);

        if (! empty($data['delivery_at'])) {
            $data['delivery_at'] = Carbon::parse($data['delivery_at']);
        }

        foreach (['maternity_case_id', 'visit_id', 'admission_id', 'department_id', 'attending_staff_id', 'theatre_case_id', 'emergency_case_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ?: null;
        }

        foreach (['delivery_mode', 'delivery_outcome', 'placenta_status', 'maternal_condition', 'status'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function arrayValues(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }

    private function log(DeliveryRecord $record, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $record->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'delivery_record_id' => $record->id,
                'labor_episode_id' => $record->labor_episode_id,
                'pregnancy_profile_id' => $record->pregnancy_profile_id,
                'status' => $record->status?->value,
                'delivery_mode' => $record->delivery_mode?->value,
                'delivery_outcome' => $record->delivery_outcome?->value,
                'newborn_count' => $record->newborn_count,
                'newborn_records_pending' => $record->newborn_records_pending,
            ]),
            'causer' => $user,
        ], $record, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
