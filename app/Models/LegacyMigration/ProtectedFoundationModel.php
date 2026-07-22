<?php

namespace App\Models\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Security\Phase3ProtectedStoreModelGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordModelReadGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use Illuminate\Database\Eloquent\Model;

abstract class ProtectedFoundationModel extends Model
{
    private bool $protectedEnvelopeBound = false;

    protected $guarded = ['id'];

    /**
     * Protected tokens and encrypted evidence never belong in ordinary model
     * serialization. Storage services expose purpose-built aggregate views.
     *
     * @var list<string>
     */
    private const PROTECTED_HIDDEN = [
        'run_token',
        'bundle_token',
        'collision_snapshot_token',
        'remediation_token',
        'supersedes_token',
        'outcome_coordinate_token',
        'event_token',
        'protected_source_token',
        'protected_target_token',
        'patient_root_token',
        'subchain_token',
        'root_token',
        'cohort_token',
        'snapshot_token',
        'coordinate_token',
        'active_coordinate_token',
        'active_source_guard_key',
        'chain_coordinate_token',
        'collision_coordinate_token',
        'dependency_chain_token',
        'idempotency_token',
        'intent_token',
        'compensation_token',
        'protected_number_token',
        'numbering_coordinate_token',
        'patient_number_result_token',
        'existing_target_evidence_token',
        'run_provenance_token',
        'evidence_issuer_token',
        'reviewer_token',
        'manual_review_owner_token',
        'release_approval_token',
        'approval_token',
        'actor_token',
        'created_by_token',
        'updated_by_token',
        'primary_guard_token',
        'primary_root_guard_key',
        'encrypted_manifest',
        'encrypted_metadata',
        'encrypted_evidence',
        'encrypted_values',
        'encrypted_evidence_metadata',
        'encrypted_mapping_payload',
        'encrypted_authoritative_rule_ids',
        'encrypted_raw_payload',
        'encrypted_field_dispositions',
        'encrypted_outcome_metadata',
        'encrypted_release_conditions',
        'encrypted_dependency_edges',
        'encrypted_population_definition',
        'encrypted_expected_equation',
        'encrypted_measured_values',
        'encrypted_exception_refs',
        'encrypted_event_payload',
        'encrypted_outcome_payload',
        'encrypted_write_set_refs',
        'encrypted_durable_fact_refs',
        'encrypted_action_payload',
        'encrypted_number_payload',
        'encrypted_explanation',
        'integrity_checksum',
        'manifest_checksum',
        'mapping_checksum',
        'protected_token',
        'encrypted_token_envelope',
        'encrypted_token_set',
        'encrypted_integrity_seal',
        'secret_reference',
        'rotation_authority_reference',
        'purge_authority_reference',
        'owner_approval_reference',
        'requested_by_authority_reference',
        'authorized_by_authority_reference',
        'aggregate_tombstone_hash',
        'event_checksum',
        'record_integrity_reference',
    ];

    protected $hidden = self::PROTECTED_HIDDEN;

    /** @return list<string> */
    public function getHidden(): array
    {
        return array_values(array_unique([...self::PROTECTED_HIDDEN, ...parent::getHidden()]));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'mandatory' => 'boolean',
            'measurement_complete' => 'boolean',
            'has_unresolved_conflict' => 'boolean',
            'is_primary' => 'boolean',
            'operator_review_required' => 'boolean',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'captured_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'valid_from' => 'immutable_datetime',
            'valid_until' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'manual_review_due_at' => 'immutable_datetime',
            'occurred_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static fn (Model $model) => Phase3ProtectedStoreModelGuard::assertWriteAllowed($model));
        static::updating(static fn (Model $model) => Phase3ProtectedStoreModelGuard::assertWriteAllowed($model));
        static::deleting(static fn () => Phase3ProtectedStoreModelGuard::denyDelete());
        static::retrieved(static fn (ProtectedFoundationModel $model) => ProtectedRecordModelReadGuard::assertReadAllowed($model));
    }

    public function markProtectedEnvelopeBound(): void
    {
        $this->protectedEnvelopeBound = true;
    }

    public function getAttribute($key): mixed
    {
        $this->assertValueAccessAllowed($key);

        return parent::getAttribute($key);
    }

    public function getAttributeValue($key)
    {
        $this->assertValueAccessAllowed($key);

        return parent::getAttributeValue($key);
    }

    public function getOriginal($key = null, $default = null)
    {
        $this->assertRawAccessAllowed($key);

        return parent::getOriginal($key, $default);
    }

    public function getRawOriginal($key = null, $default = null)
    {
        $this->assertRawAccessAllowed($key);

        return parent::getRawOriginal($key, $default);
    }

    public function getAttributes()
    {
        $this->assertRawAccessAllowed(null);

        return parent::getAttributes();
    }

    public function attributesToArray()
    {
        $this->assertRawAccessAllowed(null);

        return parent::attributesToArray();
    }

    public function fromEncryptedString($value)
    {
        $recordId = (int) parent::getAttribute($this->getKeyName());
        $controlAllowed = ProtectedStoreAccessSession::envelopeLookupAllowed()
            && in_array($this::class, [ProtectedRecordEnvelopeRecord::class, ProtectedAccessAudit::class, ProtectedKeyReference::class, ProtectedPurgeRequest::class, ProtectedRetentionPolicy::class, ProtectedTokenRotation::class], true);
        if (! $controlAllowed && ($recordId < 1 || ! ProtectedStoreAccessSession::isVerified($this::class, $recordId))) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-DECRYPTION-001');
        }

        return parent::fromEncryptedString($value);
    }

    public function toArray(): array
    {
        if ($this->protectedEnvelopeBound) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-SERIALIZATION-001');
        }

        return parent::toArray();
    }

    private function assertRawAccessAllowed(mixed $key): void
    {
        if ($key === $this->getKeyName()) {
            return;
        }
        if (! $this->protectedEnvelopeBound && $key !== null && ! $this->isProtectedField((string) $key)) {
            return;
        }
        // Eloquent's own create/update pipeline requires the complete raw
        // attribute bag before an envelope exists. A durable protected row is
        // marked bound by seal() or by the retrieved-model guard before it can
        // escape the repository boundary.
        if (! $this->protectedEnvelopeBound && $key === null) {
            return;
        }
        $controlAllowed = ProtectedStoreAccessSession::envelopeLookupAllowed()
            && in_array($this::class, [ProtectedRecordEnvelopeRecord::class, ProtectedAccessAudit::class, ProtectedKeyReference::class, ProtectedPurgeRequest::class, ProtectedRetentionPolicy::class, ProtectedTokenRotation::class], true);
        $recordId = (int) parent::getAttribute($this->getKeyName());
        if (! $controlAllowed && ! ProtectedStoreAccessSession::isVerified($this::class, $recordId)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-MODEL-RAW-ATTRIBUTE-001');
        }
    }

    private function assertValueAccessAllowed(mixed $key): void
    {
        if ($key === $this->getKeyName()) {
            return;
        }
        $recordId = (int) parent::getAttribute($this->getKeyName());
        $controlAllowed = ProtectedStoreAccessSession::envelopeLookupAllowed()
            && in_array($this::class, [ProtectedRecordEnvelopeRecord::class, ProtectedAccessAudit::class, ProtectedKeyReference::class, ProtectedPurgeRequest::class, ProtectedRetentionPolicy::class, ProtectedTokenRotation::class], true);
        if ($this->protectedEnvelopeBound
            && ! $controlAllowed
            && ($recordId < 1 || ! ProtectedStoreAccessSession::isVerified($this::class, $recordId))) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-MODEL-ATTRIBUTE-001');
        }
    }

    private function isProtectedField(string $key): bool
    {
        return in_array($key, self::PROTECTED_HIDDEN, true) || str_starts_with($key, 'encrypted_') || str_ends_with($key, '_token');
    }
}
