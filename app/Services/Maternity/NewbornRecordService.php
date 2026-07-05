<?php

namespace App\Services\Maternity;

use App\Enums\Gender;
use App\Enums\LogModule;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\NewbornSex;
use App\Models\DeliveryRecord;
use App\Models\NewbornRecord;
use App\Models\Patient;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\PatientIdGeneratorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NewbornRecordService
{
    public function __construct(
        private ActivityLogService $logger,
        private NewbornRiskAssessmentService $riskAssessment,
        private PatientIdGeneratorService $patientIds,
    ) {}

    public function create(DeliveryRecord $delivery, array $data, User $user): NewbornRecord
    {
        return DB::transaction(function () use ($delivery, $data, $user) {
            $data = $this->normalise($delivery, $data);
            $assessment = $this->riskAssessment->assess($data);
            $data['risk_flags'] = $assessment['risk_flags'];
            $data['danger_signs'] = $assessment['danger_signs'];
            $data['status'] = $data['status'] ?? ($assessment['requires_review'] ? NewbornRecordStatus::AT_RISK->value : NewbornRecordStatus::ACTIVE->value);
            $data['recorded_by'] = $data['recorded_by'] ?? $user->id;
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            $record = NewbornRecord::create($data);
            $this->syncDelivery($delivery);
            $this->log($record->fresh($this->relations()), 'NEWBORN_RECORD_CREATED', $user, $assessment);

            return $record->fresh($this->relations());
        });
    }

    public function bulkCreate(DeliveryRecord $delivery, User $user): array
    {
        $created = [];
        $expected = max(1, (int) ($delivery->newborn_count ?? 1));
        $existing = $delivery->newbornRecords()->pluck('birth_order')->filter()->map(fn ($value) => (int) $value)->all();

        DB::transaction(function () use ($delivery, $user, $expected, $existing, &$created) {
            for ($order = 1; $order <= $expected; $order++) {
                if (in_array($order, $existing, true)) {
                    continue;
                }

                $created[] = $this->create($delivery, [
                    'baby_number' => $order,
                    'birth_order' => $order,
                    'birth_time' => $delivery->delivery_at,
                    'outcome' => NewbornOutcome::UNKNOWN->value,
                    'status' => NewbornRecordStatus::ACTIVE->value,
                ], $user);
            }
        });

        $delivery->refresh();
        $this->syncDelivery($delivery);
        $this->logger->log(LogModule::MATERNITY, 'NEWBORN_RECORDS_BULK_CREATED', $delivery->toActivityContext() + [
            'metadata' => [
                'delivery_record_id' => $delivery->id,
                'created_count' => count($created),
                'expected_count' => $expected,
            ],
            'causer' => $user,
        ], $delivery, 'Newborn records bulk created');

        return $created;
    }

    public function update(NewbornRecord $record, array $data, User $user): NewbornRecord
    {
        return DB::transaction(function () use ($record, $data, $user) {
            $data = $this->normalise($record->deliveryRecord, $data, $record);
            $assessment = $this->riskAssessment->assess($data + $record->getAttributes());
            $data['risk_flags'] = $assessment['risk_flags'];
            $data['danger_signs'] = $assessment['danger_signs'];
            $data['updated_by'] = $user->id;

            $record->update($data);
            $this->syncDelivery($record->deliveryRecord);
            $this->log($record->fresh($this->relations()), 'NEWBORN_RECORD_UPDATED', $user, $assessment);

            return $record->fresh($this->relations());
        });
    }

    public function changeStatus(NewbornRecord $record, NewbornRecordStatus $status, ?NewbornOutcome $outcome, User $user): NewbornRecord
    {
        $record->update([
            'status' => $status,
            'outcome' => $outcome ?? $record->outcome,
            'updated_by' => $user->id,
        ]);
        $this->syncDelivery($record->deliveryRecord);
        $this->log($record->fresh($this->relations()), 'NEWBORN_RECORD_STATUS_CHANGED', $user);

        return $record->fresh($this->relations());
    }

    public function close(NewbornRecord $record, NewbornRecordStatus $status, ?string $reason, User $user): NewbornRecord
    {
        $record->update([
            'status' => $status,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closure_reason' => $reason ?: $record->closure_reason,
            'updated_by' => $user->id,
        ]);
        $this->syncDelivery($record->deliveryRecord);
        $this->log($record->fresh($this->relations()), 'NEWBORN_RECORD_CLOSED', $user);

        return $record->fresh($this->relations());
    }

    public function linkPatient(NewbornRecord $record, Patient $patient, User $user): NewbornRecord
    {
        $record->update([
            'newborn_patient_id' => $patient->id,
            'updated_by' => $user->id,
        ]);
        $this->log($record->fresh($this->relations()), 'NEWBORN_PATIENT_LINKED', $user, [
            'metadata' => ['newborn_patient_id' => $patient->id],
        ]);

        return $record->fresh($this->relations());
    }

    public function createPatient(NewbornRecord $record, User $user): Patient
    {
        if ($record->newborn_patient_id) {
            throw ValidationException::withMessages(['newborn_patient_id' => __('maternity.newborn_patient_already_linked')]);
        }
        if ($record->outcome === NewbornOutcome::STILLBIRTH) {
            throw ValidationException::withMessages(['outcome' => __('maternity.newborn_patient_stillbirth_blocked')]);
        }
        if (! in_array($record->sex, [NewbornSex::MALE, NewbornSex::FEMALE], true)) {
            throw ValidationException::withMessages(['sex' => __('maternity.newborn_patient_requires_sex')]);
        }

        $mother = $record->mother;
        $patient = Patient::create([
            'patient_number' => $this->patientIds->generate(),
            'first_name' => 'Baby ' . ($record->birth_order ?: $record->id),
            'last_name' => $mother?->last_name ?: 'Newborn',
            'date_of_birth' => ($record->birth_time ?: now())->toDateString(),
            'gender' => $record->sex === NewbornSex::MALE ? Gender::MALE->value : Gender::FEMALE->value,
            'phone' => $mother?->phone ?: '0000000000',
            'address' => $mother?->address,
            'city' => $mother?->city,
            'town' => $mother?->town,
            'region' => $mother?->region,
            'registered_by' => $user->id,
            'status' => 'active',
        ]);

        $this->linkPatient($record, $patient, $user);
        $this->log($record->fresh($this->relations()), 'NEWBORN_PATIENT_CREATED', $user, [
            'metadata' => ['newborn_patient_id' => $patient->id],
        ]);

        return $patient;
    }

    public function syncDelivery(DeliveryRecord $delivery): void
    {
        $delivery->refresh();
        $delivery->updateQuietly(['newborn_records_pending' => ! $delivery->newbornRecordsComplete()]);
    }

    public function relations(): array
    {
        return ['deliveryRecord', 'laborEpisode', 'pregnancyProfile', 'maternityCase', 'mother', 'newbornPatient', 'visit', 'admission', 'department', 'recordedBy'];
    }

    private function normalise(DeliveryRecord $delivery, array $data, ?NewbornRecord $existing = null): array
    {
        $data['delivery_record_id'] = $delivery->id;
        $data['labor_episode_id'] = $delivery->labor_episode_id;
        $data['pregnancy_profile_id'] = $delivery->pregnancy_profile_id;
        $data['maternity_case_id'] = $data['maternity_case_id'] ?? $delivery->maternity_case_id;
        $data['mother_patient_id'] = $delivery->patient_id;
        $data['visit_id'] = $data['visit_id'] ?? $delivery->visit_id;
        $data['admission_id'] = $data['admission_id'] ?? $delivery->admission_id;
        $data['department_id'] = $data['department_id'] ?? $delivery->department_id;
        $data['baby_number'] = $data['baby_number'] ?? $existing?->baby_number ?? $this->nextBirthOrder($delivery);
        $data['birth_order'] = $data['birth_order'] ?? $existing?->birth_order ?? $data['baby_number'];
        $data['birth_time'] = ! empty($data['birth_time'])
            ? Carbon::parse($data['birth_time'])
            : ($existing?->birth_time ?? $delivery->delivery_at);
        $data['risk_flags'] = $this->arrayValues($data['risk_flags'] ?? []);
        $data['danger_signs'] = $this->arrayValues($data['danger_signs'] ?? []);

        foreach (['maternity_case_id', 'newborn_patient_id', 'visit_id', 'admission_id', 'department_id'] as $key) {
            $data[$key] = ($data[$key] ?? null) ?: null;
        }

        foreach (['sex', 'feeding_status', 'breathing_status', 'cord_status', 'neonatal_condition', 'outcome', 'status'] as $key) {
            if (array_key_exists($key, $data) && blank($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function nextBirthOrder(DeliveryRecord $delivery): int
    {
        return ((int) $delivery->newbornRecords()->max('birth_order')) + 1;
    }

    private function arrayValues(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = [$values];
        }

        return collect($values ?: [])->filter(fn ($value) => filled($value))->unique()->values()->all();
    }

    private function log(NewbornRecord $record, string $action, User $user, array $extra = []): void
    {
        $this->logger->log(LogModule::MATERNITY, $action, $record->toActivityContext() + $extra + [
            'metadata' => array_merge($extra['metadata'] ?? [], [
                'newborn_record_id' => $record->id,
                'delivery_record_id' => $record->delivery_record_id,
                'labor_episode_id' => $record->labor_episode_id,
                'pregnancy_profile_id' => $record->pregnancy_profile_id,
                'birth_order' => $record->birth_order,
                'status' => $record->status?->value,
                'outcome' => $record->outcome?->value,
                'risk_flags_count' => count($record->risk_flags ?? []),
                'danger_signs_count' => count($record->danger_signs ?? []),
            ]),
            'causer' => $user,
        ], $record, str_replace('_', ' ', ucfirst(strtolower($action))));
    }
}
