<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

use App\Models\LegacyMigration\ProtectedAccessAudit;
use App\Models\LegacyMigration\ProtectedFoundationModel;
use App\Models\LegacyMigration\ProtectedRecordEnvelopeRecord;
use App\Models\LegacyMigration\ProtectedTokenRotation;
use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelope;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRotationLineage;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreActivationGate;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use Illuminate\Support\Facades\DB;

/** Repository-only envelope, projection and audit boundary for protected records. */
final class ProtectedRecordSecurityRepository
{
    public function __construct(
        private readonly ProtectedRecordEnvelopeFactory $factory,
        private readonly ProtectedRecordEnvelopeVerifier $verifier,
        private readonly ?ProtectedRecordRotationAuthority $rotationAuthority = null,
        private readonly ?string $connection = null,
    ) {}

    public function connectionName(): string
    {
        $name = trim((string) ($this->connection ?? config('database.default')));
        if ($name === '') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONNECTION-MISSING-001');
        }

        return $name;
    }

    public function assertConnection(string $expected): void
    {
        $expected = trim($expected);
        $default = trim((string) config('database.default'));
        if ($expected === '' || ! hash_equals($expected, $this->connectionName()) || ! hash_equals($expected, $default)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONNECTION-MISMATCH-001');
        }
    }

    public function seal(
        ProtectedStoreOperationContext $context,
        ProtectedFoundationModel $record,
        string $encodedToken,
        string $domain,
        ?int $runId,
        ?int $sourceSnapshotId,
        ?int $targetSnapshotId,
        string $accessClassification,
        string $retentionClassification,
        array $tokenSet = [],
    ): ProtectedRecordEnvelopeRecord {
        $this->assertConnection($this->connectionName());
        $this->assertRecordConnection($record);
        ProtectedStoreActivationGate::assertPopulationWriteAllowed();
        Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed($record->getConnectionName());
        if (! $record->exists || $record->getKey() === null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-NOT-DURABLE-001');
        }

        $persisted = DB::connection($record->getConnectionName())->table($record->getTable())->where($record->getKeyName(), $record->getKey())->first();
        if ($persisted === null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-NOT-DURABLE-001');
        }
        $record->setRawAttributes((array) $persisted, true);
        $recordIntegrity = $this->recordIntegrityReference($record);
        $tokenSet = $this->normalizeTokenSet($record, $encodedToken, $domain, $tokenSet);

        return ProtectedStoreAccessSession::run($context, function () use ($context, $record, $encodedToken, $domain, $runId, $sourceSnapshotId, $targetSnapshotId, $accessClassification, $retentionClassification, $recordIntegrity, $tokenSet): ProtectedRecordEnvelopeRecord {
            return DB::transaction(function () use ($context, $record, $encodedToken, $domain, $runId, $sourceSnapshotId, $targetSnapshotId, $accessClassification, $retentionClassification, $recordIntegrity, $tokenSet): ProtectedRecordEnvelopeRecord {
                $envelope = $this->factory->create(
                    $context,
                    'write',
                    $record::class,
                    (int) $record->getKey(),
                    $encodedToken,
                    $domain,
                    $runId,
                    $sourceSnapshotId,
                    $targetSnapshotId,
                    $accessClassification,
                    $retentionClassification,
                    $recordIntegrity,
                    $tokenSet,
                );
                $stored = ProtectedRecordEnvelopeRecord::query()->create([
                    'record_type' => $envelope->recordType,
                    'record_id' => $envelope->recordId,
                    'generation' => 1,
                    'run_id' => $envelope->runId,
                    'source_snapshot_id' => $envelope->sourceSnapshotId,
                    'target_snapshot_id' => $envelope->targetSnapshotId,
                    'domain' => $envelope->domain,
                    'protected_token' => $this->token($envelope)->lookupDigest(),
                    'token_environment' => $envelope->tokenEnvironment,
                    'hmac_key_id' => $envelope->keyId,
                    'hmac_key_version' => $envelope->keyVersion,
                    'canonicalization_version' => $envelope->canonicalizationVersion,
                    'record_integrity_reference' => $envelope->recordIntegrityReference,
                    'encrypted_token_envelope' => $envelope->encodedToken,
                    'encrypted_token_set' => $envelope->tokenSet,
                    'encrypted_integrity_seal' => $envelope->encodedIntegritySeal,
                    'access_classification' => $envelope->accessClassification,
                    'retention_classification' => $envelope->retentionClassification,
                    'state' => 'active',
                    'sealed_at' => now(),
                ]);
                $this->audit($context, $stored, 'write', 'authorized');
                $record->markProtectedEnvelopeBound();
                $stored->markProtectedEnvelopeBound();

                return $stored;
            }, 3);
        });
    }

    /** @param class-string<ProtectedFoundationModel> $recordType */
    /** @param array<string, mixed> $expectedAttributes */
    public function readProjection(ProtectedStoreOperationContext $context, string $recordType, int $recordId, array $expectedAttributes = []): ProtectedRecordProjection
    {
        $this->assertConnection($this->connectionName());

        return ProtectedStoreAccessSession::run($context, function () use ($context, $recordType, $recordId, $expectedAttributes): ProtectedRecordProjection {
            return ProtectedStoreAccessSession::runEnvelopeLookup(function () use ($context, $recordType, $recordId, $expectedAttributes): ProtectedRecordProjection {
                $stored = ProtectedRecordEnvelopeRecord::query()
                    ->where('record_type', $recordType)
                    ->where('record_id', $recordId)
                    ->orderByDesc('generation')
                    ->first();
                if ($stored === null) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ENVELOPE-MISSING-001');
                }
                try {
                    $envelope = $this->fromRecord($stored);
                    $verifiedToken = $this->verifier->authorize($context, 'read', $envelope);
                } catch (\Throwable $exception) {
                    $this->auditDenied($stored, 'read');
                    throw $exception;
                }
                ProtectedStoreAccessSession::markVerified('read', $envelope, $verifiedToken);

                try {
                    /** @var ProtectedFoundationModel|null $record */
                    $record = $recordType::query()->find($recordId);
                    if ($record === null) {
                        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-MISSING-001');
                    }
                    if (! hash_equals($envelope->recordIntegrityReference, $this->recordIntegrityReference($record))) {
                        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-INTEGRITY-001');
                    }
                    foreach ($expectedAttributes as $field => $expected) {
                        if (! array_key_exists($field, $record->getRawOriginal())
                            || (string) $record->getRawOriginal($field) !== (string) $expected) {
                            throw new StorageIntegrityException('Protected record resolved to incompatible lineage.');
                        }
                    }
                } catch (\Throwable $exception) {
                    $this->auditDenied($stored, 'read');
                    throw $exception;
                }
                $projection = [];
                foreach ($context->authorizedFields() as $field) {
                    if (in_array($field, $record->getHidden(), true)) {
                        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PROJECTION-FIELD-001');
                    }
                    $projection[$field] = $record->getAttribute($field);
                }
                $this->audit($context, $stored, 'read', 'authorized');

                return new ProtectedRecordProjection($recordType, $recordId, $projection);
            });
        });
    }

    public function verifyStoredEnvelope(ProtectedStoreOperationContext $context, int $envelopeId): ProtectedRecordProjection
    {
        $this->assertConnection($this->connectionName());

        return ProtectedStoreAccessSession::run($context, function () use ($context, $envelopeId): ProtectedRecordProjection {
            return ProtectedStoreAccessSession::runEnvelopeLookup(function () use ($context, $envelopeId): ProtectedRecordProjection {
                $stored = ProtectedRecordEnvelopeRecord::query()->find($envelopeId);
                if ($stored === null) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ENVELOPE-MISSING-001');
                }
                $envelope = $this->fromRecord($stored);
                try {
                    $verified = $this->verifier->authorize($context, 'read', $envelope);
                } catch (\Throwable $exception) {
                    $this->auditDenied($stored, 'read');
                    throw $exception;
                }
                ProtectedStoreAccessSession::markVerified('read', $envelope, $verified);
                /** @var ProtectedFoundationModel|null $record */
                $record = $envelope->recordType::query()->find($envelope->recordId);
                if ($record === null || ! hash_equals($envelope->recordIntegrityReference, $this->recordIntegrityReference($record))) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-INTEGRITY-001');
                }
                $this->audit($context, $stored, 'read', 'authorized');

                return new ProtectedRecordProjection($envelope->recordType, $envelope->recordId, []);
            });
        });
    }

    public function rotate(
        ProtectedStoreOperationContext $context,
        int $oldEnvelopeId,
        array $canonicalMessages,
        string $reason,
    ): ProtectedRotationLineage {
        $this->assertConnection($this->connectionName());
        ProtectedStoreActivationGate::assertPopulationWriteAllowed();
        Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed();

        return ProtectedStoreAccessSession::run($context, function () use ($context, $oldEnvelopeId, $canonicalMessages, $reason): ProtectedRotationLineage {
            return ProtectedStoreAccessSession::runEnvelopeLookup(fn (): ProtectedRotationLineage => DB::transaction(function () use ($context, $oldEnvelopeId, $canonicalMessages, $reason): ProtectedRotationLineage {
                $oldStored = ProtectedRecordEnvelopeRecord::query()->lockForUpdate()->findOrFail($oldEnvelopeId);
                $oldEnvelope = $this->fromRecord($oldStored);
                try {
                    $verifiedOldToken = $this->verifier->authorize($context, 'rotate', $oldEnvelope);
                } catch (\Throwable $exception) {
                    $this->auditDenied($oldStored, 'rotate');
                    throw $exception;
                }
                ProtectedStoreAccessSession::markVerified('rotate', $oldEnvelope, $verifiedOldToken);

                /** @var ProtectedFoundationModel|null $record */
                $record = $oldEnvelope->recordType::query()->find($oldEnvelope->recordId);
                if ($record === null || ! hash_equals($oldEnvelope->recordIntegrityReference, $this->recordIntegrityReference($record))) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-RECORD-001');
                }
                $approved = $this->rotationAuthority()->approve($context, $oldEnvelope, $canonicalMessages, $reason);
                $newTokenSet = $approved->tokenSet;
                $newEncodedToken = $approved->primaryEncodedToken;
                $newFactory = $approved->factory;
                $newVerifier = $approved->verifier;
                $newEnvelope = $newFactory->create(
                    $context,
                    'rotate',
                    $oldEnvelope->recordType,
                    $oldEnvelope->recordId,
                    $newEncodedToken,
                    $oldEnvelope->domain,
                    $oldEnvelope->runId,
                    $oldEnvelope->sourceSnapshotId,
                    $oldEnvelope->targetSnapshotId,
                    $oldEnvelope->accessClassification,
                    $oldEnvelope->retentionClassification,
                    $oldEnvelope->recordIntegrityReference,
                    $newTokenSet,
                );
                $newVerifier->authorize($context, 'rotate', $newEnvelope);
                $generation = (int) $oldStored->generation + 1;
                $newStored = ProtectedRecordEnvelopeRecord::query()->create([
                    'record_type' => $newEnvelope->recordType,
                    'record_id' => $newEnvelope->recordId,
                    'generation' => $generation,
                    'supersedes_envelope_id' => $oldStored->id,
                    'run_id' => $newEnvelope->runId,
                    'source_snapshot_id' => $newEnvelope->sourceSnapshotId,
                    'target_snapshot_id' => $newEnvelope->targetSnapshotId,
                    'domain' => $newEnvelope->domain,
                    'protected_token' => $this->token($newEnvelope)->lookupDigest(),
                    'token_environment' => $newEnvelope->tokenEnvironment,
                    'hmac_key_id' => $newEnvelope->keyId,
                    'hmac_key_version' => $newEnvelope->keyVersion,
                    'canonicalization_version' => $newEnvelope->canonicalizationVersion,
                    'record_integrity_reference' => $newEnvelope->recordIntegrityReference,
                    'encrypted_token_envelope' => $newEnvelope->encodedToken,
                    'encrypted_token_set' => $newEnvelope->tokenSet,
                    'encrypted_integrity_seal' => $newEnvelope->encodedIntegritySeal,
                    'access_classification' => $newEnvelope->accessClassification,
                    'retention_classification' => $newEnvelope->retentionClassification,
                    'state' => 'active',
                    'sealed_at' => now(),
                ]);
                $lineage = new ProtectedRotationLineage(
                    'envelope:'.$oldStored->id,
                    'envelope:'.$newStored->id,
                    $oldEnvelope->keyId,
                    $oldEnvelope->keyVersion,
                    $newEnvelope->keyId,
                    $newEnvelope->keyVersion,
                    $reason,
                    $context->authorityReference(),
                    $newEnvelope->runId,
                    $newEnvelope->sourceSnapshotId,
                    $newEnvelope->targetSnapshotId,
                    true,
                    true,
                    'verified',
                );
                $rotation = ProtectedTokenRotation::query()->create([
                    'old_envelope_id' => $oldStored->id,
                    'new_envelope_id' => $newStored->id,
                    'run_id' => $lineage->runId,
                    'source_snapshot_id' => $lineage->sourceSnapshotId,
                    'target_snapshot_id' => $lineage->targetSnapshotId,
                    'old_hmac_key_id' => $lineage->oldKeyId,
                    'old_hmac_key_version' => $lineage->oldKeyVersion,
                    'new_hmac_key_id' => $lineage->newKeyId,
                    'new_hmac_key_version' => $lineage->newKeyVersion,
                    'rotation_reason' => $lineage->reason,
                    'rotation_authority_reference' => $lineage->authorityReference,
                    'old_token_verified' => true,
                    'new_token_verified' => true,
                    'state' => 'verified',
                    'integrity_checksum' => $newFactory->keyedAuditChecksum($newEnvelope->encodedToken, $newEnvelope->domain, [
                        TypedValue::integer((int) $oldStored->id),
                        TypedValue::integer((int) $newStored->id),
                        TypedValue::string($reason),
                        TypedValue::string($context->authorityReference()),
                    ]),
                    'rotated_at' => now(),
                ]);
                $this->audit($context, $newStored, 'rotate', 'authorized', $newFactory);
                $rotation->markProtectedEnvelopeBound();

                return $lineage;
            }, 3));
        });
    }

    /**
     * Executes one existing repository compare-and-set inside verified lineage,
     * then appends a new keyed envelope generation for the changed checksum.
     *
     * @param  class-string<ProtectedFoundationModel>  $recordType
     * @param  callable(ProtectedFoundationModel): ProtectedFoundationModel  $mutation
     */
    /** @param array<string,array{encoded_token:string,domain:string}|null> $replacementTokenSet */
    public function transition(ProtectedStoreOperationContext $context, string $recordType, int $recordId, callable $mutation, array $replacementTokenSet = []): ProtectedRecordProjection
    {
        $this->assertConnection($this->connectionName());
        ProtectedStoreActivationGate::assertPopulationWriteAllowed();
        Phase3ProtectedStoreModelGuard::assertConnectionWriteAllowed();

        return ProtectedStoreAccessSession::run($context, function () use ($context, $recordType, $recordId, $mutation, $replacementTokenSet): ProtectedRecordProjection {
            return ProtectedStoreAccessSession::runEnvelopeLookup(fn (): ProtectedRecordProjection => DB::transaction(function () use ($context, $recordType, $recordId, $mutation, $replacementTokenSet): ProtectedRecordProjection {
                $oldStored = ProtectedRecordEnvelopeRecord::query()
                    ->where('record_type', $recordType)
                    ->where('record_id', $recordId)
                    ->orderByDesc('generation')
                    ->lockForUpdate()
                    ->firstOrFail();
                $oldEnvelope = $this->fromRecord($oldStored);
                try {
                    $verified = $this->verifier->authorize($context, 'transition', $oldEnvelope);
                } catch (\Throwable $exception) {
                    $this->auditDenied($oldStored, 'transition');
                    throw $exception;
                }
                ProtectedStoreAccessSession::markVerified('transition', $oldEnvelope, $verified);
                /** @var ProtectedFoundationModel|null $record */
                $record = $recordType::query()->find($recordId);
                if ($record !== null) {
                    $this->assertRecordConnection($record);
                }
                if ($record === null || ! hash_equals($oldEnvelope->recordIntegrityReference, $this->recordIntegrityReference($record))) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TRANSITION-RECORD-001');
                }
                $mutated = $mutation($record);
                if (! $mutated instanceof $recordType || (int) $mutated->getKey() !== $recordId) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TRANSITION-RESULT-001');
                }
                $this->assertRecordConnection($mutated);
                $newIntegrity = $this->recordIntegrityReference($mutated);
                if (hash_equals($newIntegrity, $oldEnvelope->recordIntegrityReference)) {
                    throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TRANSITION-INTEGRITY-UNCHANGED-001');
                }
                $newTokenSet = $oldEnvelope->tokenSet;
                foreach ($replacementTokenSet as $field => $replacement) {
                    if ($replacement === null) {
                        unset($newTokenSet[$field]);
                    } else {
                        $newTokenSet[$field] = $replacement;
                    }
                }
                $newTokenSet = $this->normalizeTokenSet($mutated, $oldEnvelope->encodedToken, $oldEnvelope->domain, $newTokenSet);
                $newEnvelope = $this->factory->create(
                    $context,
                    'transition',
                    $recordType,
                    $recordId,
                    $oldEnvelope->encodedToken,
                    $oldEnvelope->domain,
                    $oldEnvelope->runId,
                    $oldEnvelope->sourceSnapshotId,
                    $oldEnvelope->targetSnapshotId,
                    $oldEnvelope->accessClassification,
                    $oldEnvelope->retentionClassification,
                    $newIntegrity,
                    $newTokenSet,
                );
                $newStored = ProtectedRecordEnvelopeRecord::query()->create([
                    'record_type' => $recordType,
                    'record_id' => $recordId,
                    'generation' => (int) $oldStored->generation + 1,
                    'supersedes_envelope_id' => $oldStored->id,
                    'run_id' => $newEnvelope->runId,
                    'source_snapshot_id' => $newEnvelope->sourceSnapshotId,
                    'target_snapshot_id' => $newEnvelope->targetSnapshotId,
                    'domain' => $newEnvelope->domain,
                    'protected_token' => $this->token($newEnvelope)->lookupDigest(),
                    'token_environment' => $newEnvelope->tokenEnvironment,
                    'hmac_key_id' => $newEnvelope->keyId,
                    'hmac_key_version' => $newEnvelope->keyVersion,
                    'canonicalization_version' => $newEnvelope->canonicalizationVersion,
                    'record_integrity_reference' => $newEnvelope->recordIntegrityReference,
                    'encrypted_token_envelope' => $newEnvelope->encodedToken,
                    'encrypted_token_set' => $newEnvelope->tokenSet,
                    'encrypted_integrity_seal' => $newEnvelope->encodedIntegritySeal,
                    'access_classification' => $newEnvelope->accessClassification,
                    'retention_classification' => $newEnvelope->retentionClassification,
                    'state' => 'active',
                    'sealed_at' => now(),
                ]);
                $this->audit($context, $newStored, 'transition', 'authorized');
                $projection = [];
                foreach ($context->authorizedFields() as $field) {
                    if (in_array($field, $mutated->getHidden(), true)) {
                        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PROJECTION-FIELD-001');
                    }
                    $projection[$field] = $mutated->getAttribute($field);
                }

                return new ProtectedRecordProjection($recordType, $recordId, $projection);
            }, 3));
        });
    }

    private function fromRecord(ProtectedRecordEnvelopeRecord $stored): ProtectedRecordEnvelope
    {
        return new ProtectedRecordEnvelope(
            (string) $stored->record_type,
            (int) $stored->record_id,
            (string) $stored->encrypted_token_envelope,
            (string) $stored->encrypted_integrity_seal,
            (array) $stored->encrypted_token_set,
            (string) $stored->domain,
            (string) $stored->token_environment,
            (string) $stored->hmac_key_id,
            (string) $stored->hmac_key_version,
            (string) $stored->canonicalization_version,
            (string) $stored->record_integrity_reference,
            $stored->run_id === null ? null : (int) $stored->run_id,
            $stored->source_snapshot_id === null ? null : (int) $stored->source_snapshot_id,
            $stored->target_snapshot_id === null ? null : (int) $stored->target_snapshot_id,
            (string) $stored->access_classification,
            (string) $stored->retention_classification,
        );
    }

    private function recordIntegrityReference(ProtectedFoundationModel $record): string
    {
        $attributes = $record->getRawOriginal();
        if (! is_array($attributes) || $attributes === []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-INTEGRITY-MISSING-001');
        }
        foreach ($attributes as $field => $value) {
            $attributes[$field] = $this->canonicalRecordValue($value);
        }
        ksort($attributes, SORT_STRING);
        try {
            $canonical = json_encode($attributes, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-INTEGRITY-SHAPE-001');
        }

        return hash('sha256', "legacy-migration/protected-record/v1\0".$record::class."\0".$canonical);
    }

    private function canonicalRecordValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value) || is_string($value)) {
            return (string) $value;
        }
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-INTEGRITY-SHAPE-001');
    }

    /**
     * @param  array<string, array{encoded_token:string, domain:string}>  $tokenSet
     * @return array<string, array{encoded_token:string, domain:string}>
     */
    private function normalizeTokenSet(ProtectedFoundationModel $record, string $primaryEncodedToken, string $primaryDomain, array $tokenSet): array
    {
        $tokenAttributes = [];
        foreach ($record->getAttributes() as $field => $value) {
            if (str_ends_with($field, '_token') && is_string($value) && $value !== '') {
                $tokenAttributes[$field] = $value;
            }
        }
        $primary = ProtectedToken::parse($primaryEncodedToken);
        if ($tokenAttributes === [] && array_keys($tokenSet) === ['record_token']) {
            $entry = $tokenSet['record_token'];
            if (! is_array($entry) || ! is_string($entry['encoded_token'] ?? null) || ! is_string($entry['domain'] ?? null)
                || ! hash_equals($primary->encode(), ProtectedToken::parse($entry['encoded_token'])->encode())
                || ! hash_equals($primaryDomain, $entry['domain'])) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-DIGEST-001');
            }

            return $tokenSet;
        }
        $primaryFields = array_keys(array_filter($tokenAttributes, static fn (string $digest): bool => hash_equals($digest, $primary->lookupDigest())));
        if (count($primaryFields) !== 1) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PRIMARY-TOKEN-FIELD-001');
        }
        $tokenSet[$primaryFields[0]] ??= ['encoded_token' => $primaryEncodedToken, 'domain' => $primaryDomain];
        ksort($tokenSet, SORT_STRING);
        ksort($tokenAttributes, SORT_STRING);
        if (array_keys($tokenSet) !== array_keys($tokenAttributes)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-COMPLETE-001');
        }
        foreach ($tokenSet as $field => $entry) {
            if (! is_array($entry) || ! is_string($entry['encoded_token'] ?? null) || ! is_string($entry['domain'] ?? null)) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-002');
            }
            $token = ProtectedToken::parse($entry['encoded_token']);
            if (! hash_equals($tokenAttributes[$field], $token->lookupDigest())) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-DIGEST-001');
            }
        }

        return $tokenSet;
    }

    private function token(ProtectedRecordEnvelope $envelope): ProtectedToken
    {
        return ProtectedToken::parse($envelope->encodedToken);
    }

    /** @param list<TypedValue> $fields */
    public function keyedIntegrityChecksum(ProtectedStoreOperationContext $context, string $encodedToken, string $domain, array $fields): string
    {
        ProtectedStoreActivationGate::assertPopulationWriteAllowed();

        return $this->factory->keyedPurposeChecksum($context, 'write', $encodedToken, $domain, $fields);
    }

    private function rotationAuthority(): ProtectedRecordRotationAuthority
    {
        return $this->rotationAuthority ?? throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-AUTHORITY-MISSING-001');
    }

    private function assertRecordConnection(ProtectedFoundationModel $record): void
    {
        $effective = trim((string) ($record->getConnectionName() ?? config('database.default')));
        if ($effective === '' || ! hash_equals($this->connectionName(), $effective)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-RECORD-CONNECTION-MISMATCH-001');
        }
    }

    private function auditDenied(ProtectedRecordEnvelopeRecord $envelope, string $operation): void
    {
        try {
            ProtectedAccessAudit::query()->create([
                'run_id' => $envelope->run_id,
                'envelope_id' => $envelope->id,
                'purpose' => 'denied_protected_access',
                'operation' => $operation,
                'record_type' => $envelope->record_type,
                'domain' => $envelope->domain,
                'authority_reference' => 'untrusted_not_recorded',
                'result_code' => 'denied_or_tampered',
                'event_checksum' => $this->factory->keyedAuditChecksum(
                    (string) $envelope->encrypted_token_envelope,
                    (string) $envelope->domain,
                    [
                        TypedValue::integer((int) $envelope->id),
                        TypedValue::string('denied_protected_access'),
                        TypedValue::string($operation),
                        TypedValue::string('denied_or_tampered'),
                    ],
                ),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable) {
            // A malformed primary token cannot safely key a persistent audit.
            // The original denial remains fail-closed and is never masked.
        }
    }

    private function audit(ProtectedStoreOperationContext $context, ProtectedRecordEnvelopeRecord $envelope, string $operation, string $result, ?ProtectedRecordEnvelopeFactory $factory = null): void
    {
        $factory ??= $this->factory;
        ProtectedStoreAccessSession::runEnvelopeLookup(static function () use ($context, $envelope, $operation, $result, $factory): void {
            ProtectedAccessAudit::query()->create([
                'run_id' => $envelope->run_id,
                'envelope_id' => $envelope->id,
                'purpose' => $context->purpose(),
                'operation' => $operation,
                'record_type' => $envelope->record_type,
                'domain' => $envelope->domain,
                'authority_reference' => $context->authorityReference(),
                'result_code' => $result,
                'event_checksum' => $factory->keyedAuditChecksum(
                    (string) $envelope->encrypted_token_envelope,
                    (string) $envelope->domain,
                    [
                        TypedValue::integer((int) $envelope->id),
                        TypedValue::string($context->purpose()),
                        TypedValue::string($operation),
                        TypedValue::string($result),
                    ],
                ),
                'occurred_at' => now(),
            ]);
        });
    }
}
