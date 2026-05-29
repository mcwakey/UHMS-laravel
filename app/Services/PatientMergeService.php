<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAlias;
use App\Models\PatientMergeLog;
use App\Models\PatientMergeRequest;
use App\Models\User;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PatientMergeService
{
    public function __construct(
        private \App\Services\PatientMergePreviewService $previewService,
        private ?\App\Services\ActivityLogService $logger = null,
    ) {
        $this->logger = $this->logger ?: app(\App\Services\ActivityLogService::class);
    }

    public function createRequest(
        Patient $mainPatient,
        Patient $duplicatePatient,
        User $user,
        array $fieldResolution = [],
        ?string $reason = null,
        bool $approve = false,
    ): PatientMergeRequest {
        $mainPatient = $mainPatient->getFinalPatient();
        $this->validatePair($mainPatient, $duplicatePatient);

        $preview = $this->previewService->preview($mainPatient, $duplicatePatient);

        return PatientMergeRequest::create([
            'request_number' => $this->nextRequestNumber(),
            'main_patient_id' => $mainPatient->id,
            'duplicate_patient_id' => $duplicatePatient->id,
            'requested_by' => $user->id,
            'approved_by' => $approve ? $user->id : null,
            'status' => $approve ? PatientMergeRequest::STATUS_APPROVED : PatientMergeRequest::STATUS_PENDING_REVIEW,
            'reason' => $reason,
            'match_confidence' => $this->estimateMatchConfidence($mainPatient, $duplicatePatient),
            'field_resolution' => $fieldResolution,
            'preview_summary' => $preview,
            'approved_at' => $approve ? now() : null,
        ]);
    }

    public function execute(PatientMergeRequest $mergeRequest, User $user): PatientMergeRequest
    {
        if (! $mergeRequest->can_execute) {
            throw new \InvalidArgumentException('This merge request cannot be executed in its current status.');
        }

        return DB::transaction(function () use ($mergeRequest, $user) {
            $mergeRequest = PatientMergeRequest::whereKey($mergeRequest->id)->lockForUpdate()->firstOrFail();
            $mainPatient = Patient::whereKey($mergeRequest->main_patient_id)->lockForUpdate()->firstOrFail()->getFinalPatient();
            $duplicatePatient = Patient::whereKey($mergeRequest->duplicate_patient_id)->lockForUpdate()->firstOrFail();

            $this->validatePair($mainPatient, $duplicatePatient);

            if ($mergeRequest->main_patient_id !== $mainPatient->id) {
                $mergeRequest->main_patient_id = $mainPatient->id;
            }

            if ($mergeRequest->status === PatientMergeRequest::STATUS_PENDING_REVIEW) {
                $mergeRequest->status = PatientMergeRequest::STATUS_APPROVED;
                $mergeRequest->approved_by = $user->id;
                $mergeRequest->approved_at = now();
            }

            $mergeRequest->save();

            $this->log($mergeRequest, 'MERGE_STARTED', null, null, null, [
                'main_patient_number' => $mainPatient->patient_number,
                'duplicate_patient_number' => $duplicatePatient->patient_number,
            ], $user);

            $this->createAliases($mergeRequest, $mainPatient, $duplicatePatient, $user);
            $this->applyFieldResolution($mergeRequest, $mainPatient, $duplicatePatient, $user);
            $this->mergePatientInsurances($mergeRequest, $mainPatient, $duplicatePatient, $user);
            $this->reassignGenericPatientRecords($mergeRequest, $mainPatient, $duplicatePatient, $user);

            $duplicatePatient->forceFill([
                'status' => 'inactive',
                'is_active' => false,
                'merge_status' => 'MERGED',
                'merged_to_patient_id' => $mainPatient->id,
                'merged_at' => now(),
                'merged_by' => $user->id,
                'identity_confirmed_at' => $duplicatePatient->is_temporary ? now() : $duplicatePatient->identity_confirmed_at,
                'identity_confirmed_by' => $duplicatePatient->is_temporary ? $user->id : $duplicatePatient->identity_confirmed_by,
            ])->save();

            $preview = $this->previewService->preview($mainPatient->fresh(), $duplicatePatient->fresh());

            $mergeRequest->forceFill([
                'status' => PatientMergeRequest::STATUS_COMPLETED,
                'executed_by' => $user->id,
                'executed_at' => now(),
                'preview_summary' => $preview,
            ])->save();

            $this->log($mergeRequest, 'MERGE_COMPLETED', 'patients', $duplicatePatient->id, [
                'merged_to_patient_id' => null,
                'merge_status' => $duplicatePatient->getOriginal('merge_status'),
            ], [
                'merged_to_patient_id' => $mainPatient->id,
                'merge_status' => 'MERGED',
            ], $user);

            $this->logger?->log(LogModule::PATIENT_MERGE, 'PATIENT_MERGED', [
                'severity' => LogSeverity::SECURITY,
                'patient_id' => $mainPatient->id,
                'metadata' => [
                    'merge_request_id' => $mergeRequest->id,
                    'duplicate_patient_id' => $duplicatePatient->id,
                    'duplicate_patient_number' => $duplicatePatient->patient_number,
                    'main_patient_number' => $mainPatient->patient_number,
                ],
            ], $mergeRequest, 'Patient folder merged');

            return $mergeRequest->fresh(['mainPatient', 'duplicatePatient', 'logs.performedBy']);
        });
    }

    private function validatePair(Patient $mainPatient, Patient $duplicatePatient): void
    {
        if ($mainPatient->is($duplicatePatient)) {
            throw new \InvalidArgumentException('A patient folder cannot be merged into itself.');
        }

        if ($duplicatePatient->isMerged()) {
            $final = $duplicatePatient->getFinalPatient();
            throw new \InvalidArgumentException("The duplicate folder is already merged into {$final->patient_number}.");
        }

        if ($mainPatient->isMerged()) {
            throw new \InvalidArgumentException('The main patient folder is already merged. Use the final active folder.');
        }
    }

    private function nextRequestNumber(): string
    {
        $prefix = 'PMR-'.now()->format('Ymd').'-';
        $count = PatientMergeRequest::where('request_number', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function estimateMatchConfidence(Patient $mainPatient, Patient $duplicatePatient): int
    {
        $score = 0;

        foreach (['first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'ghana_card_number'] as $field) {
            $mainValue = $mainPatient->getRawOriginal($field);
            $duplicateValue = $duplicatePatient->getRawOriginal($field);

            if (filled($mainValue) && filled($duplicateValue) && (string) $mainValue === (string) $duplicateValue) {
                $score += $field === 'ghana_card_number' ? 25 : 12;
            }
        }

        return min(100, $score);
    }

    private function createAliases(PatientMergeRequest $mergeRequest, Patient $mainPatient, Patient $duplicatePatient, User $user): void
    {
        $this->createAlias(
            $mergeRequest,
            $mainPatient,
            $duplicatePatient,
            $duplicatePatient->is_temporary ? PatientAlias::TYPE_TEMPORARY_PATIENT_NUMBER : PatientAlias::TYPE_PATIENT_NUMBER,
            $duplicatePatient->patient_number,
            $user,
        );

        foreach ([PatientAlias::TYPE_GHANA_CARD => $duplicatePatient->ghana_card_number, PatientAlias::TYPE_PHONE => $duplicatePatient->phone] as $type => $value) {
            if (filled($value)) {
                $this->createAlias($mergeRequest, $mainPatient, $duplicatePatient, $type, $value, $user);
            }
        }
    }

    private function createAlias(PatientMergeRequest $mergeRequest, Patient $mainPatient, Patient $duplicatePatient, string $type, ?string $value, User $user): void
    {
        if (blank($value)) {
            return;
        }

        $normalized = PatientAlias::normalize($value);
        $alias = PatientAlias::where('alias_type', $type)->where('normalized_alias_value', $normalized)->first();

        if ($alias && (int) $alias->patient_id !== (int) $mainPatient->id) {
            $this->log($mergeRequest, 'ALIAS_SKIPPED', 'patient_aliases', $alias->id, null, [
                'alias_type' => $type,
                'alias_value' => $value,
                'existing_patient_id' => $alias->patient_id,
            ], $user);

            return;
        }

        $alias ??= PatientAlias::create([
            'patient_id' => $mainPatient->id,
            'source_patient_id' => $duplicatePatient->id,
            'alias_type' => $type,
            'alias_value' => $value,
            'normalized_alias_value' => $normalized,
            'metadata' => ['patient_merge_request_id' => $mergeRequest->id],
            'created_by' => $user->id,
        ]);

        $this->log($mergeRequest, 'ALIAS_CREATED', 'patient_aliases', $alias->id, null, [
            'alias_type' => $type,
            'alias_value' => $value,
        ], $user);
    }

    private function applyFieldResolution(PatientMergeRequest $mergeRequest, Patient $mainPatient, Patient $duplicatePatient, User $user): void
    {
        $allowedFields = array_keys($this->previewService->demographicFields());
        $resolution = $mergeRequest->field_resolution ?? [];
        $changes = [];

        foreach ($resolution as $field => $source) {
            if (! in_array($field, $allowedFields, true) || $source !== 'duplicate') {
                continue;
            }

            $value = $duplicatePatient->getRawOriginal($field);
            if (blank($value)) {
                continue;
            }

            $changes[$field] = $value;
        }

        if ($changes === []) {
            return;
        }

        $oldValues = Arr::only($mainPatient->getRawOriginal(), array_keys($changes));
        $mainPatient->forceFill($changes)->save();

        $this->log($mergeRequest, 'MAIN_DEMOGRAPHICS_UPDATED', 'patients', $mainPatient->id, $oldValues, $changes, $user);
    }

    private function mergePatientInsurances(PatientMergeRequest $mergeRequest, Patient $mainPatient, Patient $duplicatePatient, User $user): void
    {
        if (! $this->previewService->tableColumnExists('patient_insurances', 'patient_id')) {
            return;
        }

        $duplicateInsurances = DB::table('patient_insurances')
            ->where('patient_id', $duplicatePatient->id)
            ->orderBy('id')
            ->get();

        foreach ($duplicateInsurances as $duplicateInsurance) {
            $providerId = $duplicateInsurance->insurance_provider_id ?? null;
            $matchingMainInsurance = $providerId
                ? DB::table('patient_insurances')
                    ->where('patient_id', $mainPatient->id)
                    ->where('insurance_provider_id', $providerId)
                    ->first()
                : null;

            if ($matchingMainInsurance) {
                $this->movePatientInsuranceReferences($duplicateInsurance->id, $matchingMainInsurance->id);

                DB::table('patient_insurances')
                    ->where('id', $duplicateInsurance->id)
                    ->update(array_filter([
                        'is_active' => false,
                        'is_primary' => false,
                        'updated_at' => $this->previewService->tableColumnExists('patient_insurances', 'updated_at') ? now() : null,
                    ], fn ($value) => $value !== null));

                $this->log($mergeRequest, 'PATIENT_INSURANCE_DEDUPED', 'patient_insurances', $duplicateInsurance->id, [
                    'patient_id' => $duplicatePatient->id,
                    'patient_insurance_id' => $duplicateInsurance->id,
                ], [
                    'references_moved_to_patient_insurance_id' => $matchingMainInsurance->id,
                ], $user);

                continue;
            }

            DB::table('patient_insurances')
                ->where('id', $duplicateInsurance->id)
                ->update(array_filter([
                    'patient_id' => $mainPatient->id,
                    'updated_at' => $this->previewService->tableColumnExists('patient_insurances', 'updated_at') ? now() : null,
                ], fn ($value) => $value !== null));

            $this->log($mergeRequest, 'PATIENT_INSURANCE_MOVED', 'patient_insurances', $duplicateInsurance->id, [
                'patient_id' => $duplicatePatient->id,
            ], [
                'patient_id' => $mainPatient->id,
            ], $user);
        }
    }

    private function movePatientInsuranceReferences(int $fromInsuranceId, int $toInsuranceId): void
    {
        $references = [
            ['table' => 'patient_insurances', 'column' => 'card_holder_insurance_id'],
            ['table' => 'visits', 'column' => 'visit_insurance_id'],
            ['table' => 'invoices', 'column' => 'patient_insurance_id'],
            ['table' => 'invoice_items', 'column' => 'patient_insurance_id'],
            ['table' => 'claims', 'column' => 'patient_insurance_id'],
            ['table' => 'insurance_usages', 'column' => 'patient_insurance_id'],
            ['table' => 'insurance_verifications', 'column' => 'patient_insurance_id'],
        ];

        foreach ($references as $reference) {
            if (! $this->previewService->tableColumnExists($reference['table'], $reference['column'])) {
                continue;
            }

            DB::table($reference['table'])
                ->where($reference['column'], $fromInsuranceId)
                ->update([$reference['column'] => $toInsuranceId]);
        }
    }

    private function reassignGenericPatientRecords(PatientMergeRequest $mergeRequest, Patient $mainPatient, Patient $duplicatePatient, User $user): void
    {
        foreach ($this->previewService->recordTables() as $definition) {
            if (($definition['handler'] ?? 'generic') !== 'generic') {
                continue;
            }

            if (! $this->previewService->tableColumnExists($definition['table'], 'patient_id')) {
                continue;
            }

            $count = DB::table($definition['table'])
                ->where('patient_id', $duplicatePatient->id)
                ->count();

            if ($count === 0) {
                continue;
            }

            $updated = DB::table($definition['table'])
                ->where('patient_id', $duplicatePatient->id)
                ->update(['patient_id' => $mainPatient->id]);

            $this->log($mergeRequest, 'PATIENT_RECORDS_REASSIGNED', $definition['table'], null, [
                'patient_id' => $duplicatePatient->id,
                'count' => $count,
            ], [
                'patient_id' => $mainPatient->id,
                'updated' => $updated,
            ], $user);
        }
    }

    private function log(
        PatientMergeRequest $mergeRequest,
        string $action,
        ?string $tableName,
        ?int $recordId,
        ?array $oldValues,
        ?array $newValues,
        User $user,
    ): void {
        PatientMergeLog::create([
            'patient_merge_request_id' => $mergeRequest->id,
            'main_patient_id' => $mergeRequest->main_patient_id,
            'duplicate_patient_id' => $mergeRequest->duplicate_patient_id,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_by' => $user->id,
            'occurred_at' => now(),
        ]);
    }
}
