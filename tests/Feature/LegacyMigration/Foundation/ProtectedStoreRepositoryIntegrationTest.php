<?php

namespace Tests\Feature\LegacyMigration\Foundation;

use App\Models\LegacyMigration\AtomicIntent;
use App\Models\LegacyMigration\Checkpoint;
use App\Models\LegacyMigration\CompensationRecord;
use App\Models\LegacyMigration\ContractBundle;
use App\Models\LegacyMigration\Crosswalk;
use App\Models\LegacyMigration\IdempotencyRecord;
use App\Models\LegacyMigration\MigrationRun;
use App\Models\LegacyMigration\NumberReservation;
use App\Models\LegacyMigration\ProtectedKeyReference;
use App\Models\LegacyMigration\ProtectedPurgeRequest;
use App\Models\LegacyMigration\ProtectedRecordEnvelopeRecord;
use App\Models\LegacyMigration\ProtectedRetentionPolicy;
use App\Models\LegacyMigration\QuarantineRoot;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAuthority;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\EnvelopeVerifiedIntegrityAuthority;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\ProvenanceCompletenessValidator;
use App\Services\LegacyMigration\Foundation\Security\RemediationAdmissionService;
use App\Services\LegacyMigration\Foundation\Security\RemediationCandidate;
use App\Services\LegacyMigration\Foundation\Security\TokenContextMismatchException;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TokenDomainRegistry;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Storage\CompareAndSet;
use App\Services\LegacyMigration\Foundation\Storage\CrosswalkRepository;
use App\Services\LegacyMigration\Foundation\Storage\IdempotencyRepository;
use App\Services\LegacyMigration\Foundation\Storage\MigrationAuditRepository;
use App\Services\LegacyMigration\Foundation\Storage\NumberReservationRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedEvidenceRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedLifecycleRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use App\Services\LegacyMigration\Foundation\Storage\QuarantineReleaseGuard;
use App\Services\LegacyMigration\Foundation\Storage\QuarantineRepository;
use App\Services\LegacyMigration\Foundation\Storage\ReconciliationRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryRepository;
use App\Services\LegacyMigration\Foundation\Storage\RunManifestRepository;
use App\Services\LegacyMigration\Foundation\Storage\SnapshotRepository;
use App\Services\LegacyMigration\Foundation\Storage\StorageIntegrityException;
use App\Services\LegacyMigration\Foundation\Allocation\ReservationCoordinateContextFactory;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProtectedStoreRepositoryIntegrationTest extends TestCase
{
    /** @var list<object> */
    private array $migrations = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        config(['app.key' => 'base64:'.base64_encode(str_repeat('P', 32))]);
        DB::statement('PRAGMA foreign_keys = ON');
        foreach ([
            '2026_07_22_000110_create_legacy_migration_run_foundation_tables.php',
            '2026_07_22_000111_create_legacy_migration_protected_store_tables.php',
            '2026_07_22_000112_create_legacy_migration_recovery_tables.php',
            '2026_07_22_000114_create_legacy_migration_protected_lifecycle_tables.php',
        ] as $file) {
            $migration = require database_path('migrations/'.$file);
            $migration->up();
            $this->migrations[] = $migration;
        }
    }

    protected function tearDown(): void
    {
        ProtectedStoreAccessSession::reset();
        foreach (array_reverse($this->migrations) as $migration) {
            $migration->down();
        }
        parent::tearDown();
    }

    public function test_repository_seals_projects_and_audits_while_direct_model_read_and_serialization_fail(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $token = $tokens->tokenize(new TokenDomain('migration_run'), $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::BUNDLE')]))->encode();
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($token));
        $context = $this->context(['write', 'read']);

        $envelope = $security->seal($context, $bundle, $token, 'migration_run', null, null, null, 'protected', 'migration-lineage');
        $this->assertGreaterThan(0, $envelope->id);
        $this->assertSame(1, DB::table('legacy_migration_protected_access_audits')->count());

        $projection = $security->readProjection($context, ContractBundle::class, (int) $bundle->id);
        $this->assertSame(['bundle_version' => 'SYNTHETIC-P3B-1'], $projection->fields);
        $this->assertSame(2, DB::table('legacy_migration_protected_access_audits')->count());

        try {
            $envelope->encrypted_token_envelope;
            $this->fail('Created envelope exposed decrypted token material outside repository scope.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-STORE-MODEL-ATTRIBUTE-001', $exception->getMessage());
        }
        try {
            ProtectedRecordEnvelopeRecord::query()->findOrFail($envelope->id);
            $this->fail('Direct envelope model retrieval was allowed.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-STORE-ENVELOPE-DIRECT-READ-001', $exception->getMessage());
        }

        try {
            $bundle->encrypted_manifest;
            $this->fail('Protected attribute decryption was allowed outside the verified purpose session.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-STORE-MODEL-ATTRIBUTE-001', $exception->getMessage());
        }

        try {
            $bundle->toArray();
            $this->fail('Secured model serialization was allowed.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-STORE-SERIALIZATION-001', $exception->getMessage());
        }

        foreach ([
            fn () => $bundle->getAttributeValue('encrypted_manifest'),
            fn () => $bundle->getOriginal('encrypted_manifest'),
            fn () => $bundle->getRawOriginal('encrypted_manifest'),
            fn () => $bundle->attributesToArray(),
        ] as $bypass) {
            try {
                $bypass();
                $this->fail('A low-level model accessor bypassed the verified repository projection.');
            } catch (ProtectedStoreAccessDeniedException) {
                $this->addToAssertionCount(1);
            }
        }
        $ciphertext = (string) DB::table('legacy_migration_contract_bundles')->where('id', $bundle->id)->value('encrypted_manifest');
        $this->assertStringNotContainsString('SYNTHETIC-ONLY-P3B', $ciphertext);
        try {
            $bundle->fromEncryptedString($ciphertext);
            $this->fail('Direct cast decryption bypassed the verified repository projection.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-STORE-DECRYPTION-001', $exception->getMessage());
        }

        $this->expectExceptionMessage('LM-SEC-STORE-DIRECT-MODEL-READ-001');
        ContractBundle::query()->findOrFail($bundle->id);
    }

    public function test_denied_access_is_audited_with_trusted_redacted_metadata(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $token = $this->encodedToken($tokens, $encoder, 'denied-audit');
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($token));
        $security->seal($this->context(['write']), $bundle, $token, 'migration_run', null, null, null, 'protected', 'migration-lineage');
        $untrusted = new ProtectedStoreOperationContext(
            'synthetic_repository_test', ['read'], ['migration_run'], 'testing', null, null, null,
            'protected', 'migration-lineage', 'CALLER-SELF-ASSERTED-AUTHORITY', ['bundle_version'],
        );

        try {
            $security->readProjection($untrusted, ContractBundle::class, (int) $bundle->id);
            $this->fail('Untrusted authority unexpectedly read a protected record.');
        } catch (ProtectedStoreAccessDeniedException) {
            $this->addToAssertionCount(1);
        }

        $audit = DB::table('legacy_migration_protected_access_audits')->orderByDesc('id')->first();
        $this->assertSame('denied_protected_access', $audit->purpose);
        $this->assertSame('untrusted_not_recorded', $audit->authority_reference);
        $this->assertSame('denied_or_tampered', $audit->result_code);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $audit->event_checksum);
    }

    public function test_query_builder_sees_only_ciphertext_and_cannot_mass_update_or_delete(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $token = $tokens->tokenize(new TokenDomain('migration_run'), $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::BUNDLE')]))->encode();
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($token));
        $security->seal($this->context(['write']), $bundle, $token, 'migration_run', null, null, null, 'protected', 'migration-lineage');

        $stored = DB::table('legacy_migration_protected_record_envelopes')->first();
        $this->assertStringNotContainsString('lmt1.', (string) $stored->encrypted_token_envelope);
        $this->assertStringNotContainsString($token, json_encode($stored, JSON_THROW_ON_ERROR));

        try {
            DB::table('legacy_migration_protected_record_envelopes')->where('id', $stored->id)->update(['state' => 'tampered']);
            $this->fail('Mass update bypassed append-only protection.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_protected_record_envelopes')->where('id', $stored->id)->delete();
    }

    public function test_bare_sha256_string_cannot_be_admitted_as_a_protected_envelope(): void
    {
        [$security] = $this->security();
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes());

        $this->expectException(\InvalidArgumentException::class);
        $security->seal($this->context(['write']), $bundle, hash('sha256', 'SYNTHETIC-ONLY-P3B'), 'migration_run', null, null, null, 'protected', 'migration-lineage');
    }

    public function test_model_and_transition_result_connections_cannot_escape_the_security_boundary(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $encoded = $this->encodedToken($tokens, $encoder, 'connection-bound-record');
        $tokenSet = $this->tokenSet(['bundle_token' => $encoded]);
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($encoded));
        $bundle->setConnection('synthetic_mismatched_connection');
        $beforeEnvelopes = DB::table('legacy_migration_protected_record_envelopes')->count();
        $beforeAudits = DB::table('legacy_migration_protected_access_audits')->count();
        try {
            $security->seal(
                $this->context(['write']), $bundle, $encoded, 'migration_run',
                null, null, null, 'protected', 'migration-lineage', $tokenSet,
            );
            self::fail('A durable record on another connection was sealed.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            self::assertStringContainsString('LM-SEC-STORE-RECORD-CONNECTION-MISMATCH-001', $exception->getMessage());
        }
        self::assertSame($beforeEnvelopes, DB::table('legacy_migration_protected_record_envelopes')->count());
        self::assertSame($beforeAudits, DB::table('legacy_migration_protected_access_audits')->count());

        $bundle->setConnection((string) config('database.default'));
        $security->seal(
            $this->context(['write']), $bundle, $encoded, 'migration_run',
            null, null, null, 'protected', 'migration-lineage', $tokenSet,
        );
        $beforeEnvelopes = DB::table('legacy_migration_protected_record_envelopes')->count();
        $beforeAudits = DB::table('legacy_migration_protected_access_audits')->count();
        $beforeVersion = DB::table('legacy_migration_contract_bundles')->where('id', $bundle->id)->value('bundle_version');
        try {
            $security->transition(
                $this->context(['transition']),
                ContractBundle::class,
                (int) $bundle->id,
                static function ($record) {
                    $record->setConnection('synthetic_mismatched_connection');

                    return $record;
                },
            );
            self::fail('A transition result switched to another connection.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            self::assertStringContainsString('LM-SEC-STORE-RECORD-CONNECTION-MISMATCH-001', $exception->getMessage());
        }
        self::assertSame($beforeVersion, DB::table('legacy_migration_contract_bundles')->where('id', $bundle->id)->value('bundle_version'));
        self::assertSame($beforeEnvelopes, DB::table('legacy_migration_protected_record_envelopes')->count());
        self::assertSame($beforeAudits, DB::table('legacy_migration_protected_access_audits')->count());
    }

    public function test_key_rotation_appends_verified_lineage_without_overwriting_the_old_envelope(): void
    {
        $oldKey = '56789abcdef01234FABCDE!@#$%^&*()-+=old-key';
        $newKey = '6789abcdef01234FABCDE!@#$%^&*()-+=new-key5';
        [, $oldTokens, $encoder, $oldFactory, $oldVerifier] = $this->security('v1', $oldKey);
        [$newSecurity, , , $newFactory, $newVerifier] = $this->security('v2', $newKey);
        $message = $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::BUNDLE')]);
        $oldToken = $oldTokens->tokenize(new TokenDomain('migration_run'), $message)->encode();
        $rotationTokens = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac', 'key_version' => 'v2', 'key' => $newKey, 'algorithm' => 'sha256',
                'previous_keys' => [['key_id' => 'migration-hmac', 'key_version' => 'v1', 'key' => $oldKey]],
            ]),
            new CanonicalizationVersionRegistry,
            'testing',
            new TokenDomainRegistry(['migration_run', 'artifact_integrity']),
        );
        $oldSecurity = new ProtectedRecordSecurityRepository(
            $oldFactory,
            $oldVerifier,
            new PinnedProtectedRecordRotationAuthority(
                $rotationTokens,
                $newFactory,
                $newVerifier,
                'SYNTHETIC-AUTHORITY',
                ['scheduled-owner-authorized-rotation'],
            ),
        );
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($oldToken));
        $oldEnvelope = $oldSecurity->seal($this->context(['write']), $bundle, $oldToken, 'migration_run', null, null, null, 'protected', 'migration-lineage');

        foreach ([
            ['messages' => ['bundle_token' => $message], 'reason' => 'caller-invented-reason'],
            ['messages' => ['bundle_token' => $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::FORGED')])], 'reason' => 'scheduled-owner-authorized-rotation'],
        ] as $attempt) {
            try {
                $oldSecurity->rotate($this->context(['rotate']), (int) $oldEnvelope->id, $attempt['messages'], $attempt['reason']);
                $this->fail('Caller-controlled rotation input was accepted.');
            } catch (ProtectedStoreAccessDeniedException|TokenContextMismatchException) {
                $this->assertSame(1, DB::table('legacy_migration_protected_record_envelopes')->count());
            }
        }

        $rotation = $oldSecurity->rotate(
            $this->context(['rotate']),
            (int) $oldEnvelope->id,
            ['bundle_token' => $message],
            'scheduled-owner-authorized-rotation',
        );

        $this->assertSame('v1', $rotation->oldKeyVersion);
        $this->assertSame('v2', $rotation->newKeyVersion);
        $this->assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->count());
        $this->assertSame(1, DB::table('legacy_migration_protected_token_rotations')->count());
        $this->assertSame('SYNTHETIC-P3B-1', $newSecurity->readProjection($this->context(['read']), ContractBundle::class, (int) $bundle->id)->fields['bundle_version']);
    }

    public function test_container_rotation_uses_active_and_retained_manifest_keys_and_rejects_revoked_or_expired_old_keys(): void
    {
        $recoveryReference = hash('sha256', 'SYNTHETIC-ONLY-P3B::container-recovery-authority');
        $rotationReference = hash('sha256', 'SYNTHETIC-ONLY-P3B::container-rotation-authority');
        $this->app->instance(RecoveryJournalAuthority::class, $this->recoveryAuthority($recoveryReference));
        putenv('P3B_SYNTHETIC_ROTATION_V1=56789abcdef01234FABCDE!@#$%^&*()-+=v1key');
        putenv('P3B_SYNTHETIC_ROTATION_V2=6789abcdef01234FABCDE!@#$%^&*()-+=v2key5');
        putenv('P3B_SYNTHETIC_ROTATION_V3=789abcdef01234FABCDE!@#$%^&*()-+=v3key56');
        $domains = ['idempotency', 'migration_run', 'source_snapshot', 'target_snapshot', 'recovery', 'migration_audit', 'artifact_integrity'];
        config([
            'legacy-migration.key_provider.rotation_authority_reference' => $rotationReference,
            'legacy-migration.key_provider.approved_rotation_reasons' => ['scheduled-owner-authorized-rotation'],
            'legacy-migration.protected_store.access_classification' => 'protected',
            'legacy-migration.retention.protected' => 'migration-lineage',
            'legacy-migration.snapshots.authoritative_capture_enabled' => false,
            'legacy-migration.key_provider.references' => [
                $this->keyReference('v1', 'env://P3B_SYNTHETIC_ROTATION_V1', $rotationReference, $domains, true),
            ],
        ]);
        $this->forgetContainerSecurity();
        /** @var ProtectedRecordSecurityRepository $v1Repository */
        $v1Repository = $this->app->make(ProtectedRecordSecurityRepository::class);
        $v1Tokens = $this->app->make(HmacTokenService::class);
        $encoder = $this->app->make(CanonicalTypedMessageEncoder::class);
        $message = $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::CONTAINER-ROTATION')]);
        $oldToken = $v1Tokens->tokenize(new TokenDomain('migration_run'), $message)->encode();
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($oldToken));
        $oldEnvelope = $v1Repository->seal(
            new ProtectedStoreOperationContext('foundation_recovery', ['write'], ['migration_run'], 'testing', null, null, null, 'protected', 'migration-lineage', $recoveryReference),
            $bundle, $oldToken, 'migration_run', null, null, null, 'protected', 'migration-lineage',
        );

        config(['legacy-migration.key_provider.references' => [
            $this->keyReference('v2', 'env://P3B_SYNTHETIC_ROTATION_V2', $rotationReference, $domains, true),
            array_replace($this->keyReference('v1', 'env://P3B_SYNTHETIC_ROTATION_V1', $rotationReference, $domains, false), [
                'retired_at' => '2026-07-21T00:00:00Z', 'verification_expires_at' => '2027-07-21T00:00:00Z',
            ]),
        ]]);
        $this->forgetContainerSecurity();
        /** @var ProtectedRecordSecurityRepository $v2Repository */
        $v2Repository = $this->app->make(ProtectedRecordSecurityRepository::class);
        $lineage = $v2Repository->rotate(
            new ProtectedStoreOperationContext('protected_key_rotation', ['rotate'], ['migration_run'], 'testing', null, null, null, 'protected', 'migration-lineage', $rotationReference),
            (int) $oldEnvelope->id,
            ['bundle_token' => $message],
            'scheduled-owner-authorized-rotation',
        );
        $this->assertSame('v1', $lineage->oldKeyVersion);
        $this->assertSame('v2', $lineage->newKeyVersion);

        foreach ([
            ['revoked' => true, 'verification_expires_at' => '2027-07-21T00:00:00Z'],
            ['revoked' => false, 'verification_expires_at' => '2026-07-21T00:00:00Z'],
        ] as $retainedState) {
            config(['legacy-migration.key_provider.references' => [
                $this->keyReference('v3', 'env://P3B_SYNTHETIC_ROTATION_V3', $rotationReference, $domains, true),
                array_replace($this->keyReference('v2', 'env://P3B_SYNTHETIC_ROTATION_V2', $rotationReference, $domains, false), [
                    'retired_at' => '2026-07-21T00:00:00Z',
                    ...$retainedState,
                ]),
            ]]);
            $this->forgetContainerSecurity();
            try {
                $this->app->make(ProtectedRecordSecurityRepository::class)->rotate(
                    new ProtectedStoreOperationContext('protected_key_rotation', ['rotate'], ['migration_run'], 'testing', null, null, null, 'protected', 'migration-lineage', $rotationReference),
                    (int) DB::table('legacy_migration_protected_record_envelopes')->max('id'),
                    ['bundle_token' => $message],
                    'scheduled-owner-authorized-rotation',
                );
                $this->fail('Revoked or expired retained key authorized a rotation.');
            } catch (ProtectedStoreAccessDeniedException) {
                $this->assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->count());
            }
        }

        putenv('P3B_SYNTHETIC_ROTATION_V1');
        putenv('P3B_SYNTHETIC_ROTATION_V2');
        putenv('P3B_SYNTHETIC_ROTATION_V3');
    }

    public function test_lifecycle_writes_are_keyed_sealed_audited_and_reject_caller_checksums(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $repository = new ProtectedLifecycleRepository($security);
        $context = $this->context(['write', 'read'], fields: []);
        $keyToken = $this->encodedToken($tokens, $encoder, 'key-reference');

        try {
            $repository->appendKeyReferenceProtected($context, ['integrity_checksum' => str_repeat('a', 64)], $keyToken, 'migration_run');
            $this->fail('Caller-supplied lifecycle integrity checksum was accepted.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-LIFECYCLE-CALLER-CHECKSUM-001', $exception->getMessage());
        }

        $key = $repository->appendKeyReferenceProtected($context, [
            'token_environment' => 'testing', 'domain' => 'migration_run',
            'hmac_key_id' => 'migration-hmac', 'hmac_key_version' => 'v1',
            'secret_reference' => 'vault://synthetic/migration', 'activated_at' => now(),
            'rotation_authority_reference' => 'SYNTHETIC-AUTHORITY',
            'revoked' => false, 'active_for_signing' => true,
        ], $keyToken, 'migration_run');
        $this->assertGreaterThan(0, $key->id);

        $policyToken = $this->encodedToken($tokens, $encoder, 'retention-policy');
        $policy = $repository->appendRetentionPolicyProtected($context, [
            'policy_reference' => 'SYNTHETIC-POLICY', 'policy_version' => 'v1',
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
            'minimum_retention_days' => 365, 'legal_hold' => true, 'operational_hold' => false,
            'review_at' => now()->addYear(), 'purge_enabled' => false,
            'purge_authority_reference' => null, 'owner_approval_reference' => null,
            'retain_tombstone' => true, 'active' => false,
        ], $policyToken, 'migration_run');

        $bundleToken = $this->encodedToken($tokens, $encoder, 'lifecycle-bundle');
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($bundleToken));
        $bundleEnvelope = $security->seal($this->context(['write']), $bundle, $bundleToken, 'migration_run', null, null, null, 'protected', 'migration-lineage');
        $purgeToken = $this->encodedToken($tokens, $encoder, 'purge-request');
        $purge = $repository->recordBlockedPurgeRequestProtected(
            $context,
            (int) $bundleEnvelope->id,
            (int) $policy->id,
            [
                'request_reference' => 'SYNTHETIC-PURGE-REQUEST',
                'requested_by_authority_reference' => 'SYNTHETIC-AUTHORITY',
                'integrity_verified' => true, 'lineage_preservation_verified' => true,
                'tombstone_required' => true, 'aggregate_tombstone_hash' => null,
                'requested_at' => now(),
            ],
            $purgeToken,
            'migration_run',
        );

        $this->assertSame('blocked_pending_owner_policy', DB::table('legacy_migration_protected_purge_requests')->where('id', $purge->id)->value('state'));
        foreach ([ProtectedKeyReference::class, ProtectedRetentionPolicy::class, ProtectedPurgeRequest::class] as $type) {
            $this->assertSame(1, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', $type)->count());
        }
        $this->assertSame(0, DB::table('legacy_migration_protected_key_references')->whereRaw('integrity_checksum = ?', [str_repeat('a', 64)])->count());
    }

    public function test_remediation_and_provenance_require_real_repository_verified_envelopes(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $bundleToken = $this->encodedToken($tokens, $encoder, 'claims-bundle');
        $bundle = (new RunManifestRepository(security: $security))->appendContractBundleProtected(
            $this->context(['write']), $this->bundleAttributes($bundleToken), $this->tokenSet(['bundle_token' => $bundleToken]),
        );
        $runTokens = $this->tokenSet([
            'run_token' => $this->encodedToken($tokens, $encoder, 'claims-run'),
            'cohort_token' => $this->encodedToken($tokens, $encoder, 'claims-cohort'),
        ]);
        $run = (new RunManifestRepository(security: $security))->createRunProtected(
            $this->context(['write']), $this->runAttributes((int) $bundle->id, $runTokens), $runTokens,
        );
        $sourceTokens = $this->tokenSet([
            'snapshot_token' => $this->encodedToken($tokens, $encoder, 'claims-source'),
            'coordinate_token' => $this->encodedToken($tokens, $encoder, 'claims-coordinate'),
        ]);
        $source = (new SnapshotRepository($security))->appendProtected(
            $this->context(['write'], (int) $run->id),
            $this->snapshotAttributes((int) $run->id, 'source', $sourceTokens),
            $sourceTokens,
        );
        $at = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $approvedAt = $at->modify('-1 day');
        $remediationTokens = $this->tokenSet([
            'protected_source_token' => $this->encodedToken($tokens, $encoder, 'claims-remediation-source'),
            'remediation_token' => $this->encodedToken($tokens, $encoder, 'claims-remediation'),
            'evidence_issuer_token' => $this->encodedToken($tokens, $encoder, 'claims-issuer'),
            'reviewer_token' => $this->encodedToken($tokens, $encoder, 'claims-reviewer'),
        ]);
        $sourceReference = ProtectedToken::parse($remediationTokens['protected_source_token']['encoded_token'])->lookupDigest();
        $remediationReference = ProtectedToken::parse($remediationTokens['remediation_token']['encoded_token'])->lookupDigest();
        $remediationClaims = [
            'remediation_reference' => $remediationReference, 'source_token_reference' => $sourceReference,
            'field_rule' => 'patient.dob', 'value_fingerprint' => str_repeat('a', 64), 'precedence_ordinal' => 1,
            'approved' => true, 'revoked' => false, 'conflicted' => false, 'superseded_by_reference' => null,
            'approved_at' => $approvedAt->format(DATE_ATOM), 'expires_at' => null,
            'run_id' => (int) $run->id, 'source_snapshot_id' => (int) $source->id, 'target_snapshot_id' => null,
            'authority_evidence_reference' => 'OWNER-DIRECTIVE',
        ];
        $remediation = (new ProtectedEvidenceRepository($security))->appendRemediationProtected(
            $this->context(['write'], (int) $run->id, (int) $source->id),
            [
                'run_id' => (int) $run->id, 'source_snapshot_id' => (int) $source->id, 'domain' => 'migration_run',
                'protected_source_token' => $sourceReference, 'remediation_token' => $remediationReference,
                'evidence_type' => 'patient.dob',
                'evidence_issuer_token' => ProtectedToken::parse($remediationTokens['evidence_issuer_token']['encoded_token'])->lookupDigest(),
                'reviewer_token' => ProtectedToken::parse($remediationTokens['reviewer_token']['encoded_token'])->lookupDigest(),
                'approval_state' => 'approved', 'has_unresolved_conflict' => false,
                'valid_from' => $approvedAt, 'valid_until' => null, 'revoked_at' => null,
                'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
                'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
                'state' => 'approved', 'encrypted_values' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
                'integrity_checksum' => EnvelopeVerifiedIntegrityAuthority::claimHash('remediation', $remediationClaims),
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
            ],
            $remediationTokens,
        );
        $authority = EnvelopeVerifiedIntegrityAuthority::remediation(
            $security,
            $this->context(['read'], (int) $run->id, (int) $source->id, null, []),
            (int) $remediation->id,
        );
        $valid = new RemediationCandidate(
            $remediationReference, $sourceReference, 'patient.dob', str_repeat('a', 64), 1,
            true, false, false, null, $approvedAt, null,
            (int) $run->id, (int) $source->id, null, 'OWNER-DIRECTIVE', $authority,
        );
        $winner = (new RemediationAdmissionService)->admit([$valid], $sourceReference, 'patient.dob', (int) $run->id, (int) $source->id, null, $at);
        $this->assertSame($remediationReference, $winner->remediationReference);

        $provenanceTokens = $this->tokenSet([
            'protected_source_token' => $this->encodedToken($tokens, $encoder, 'claims-provenance-source'),
            'patient_root_token' => $this->encodedToken($tokens, $encoder, 'claims-patient-root'),
            'subchain_token' => $this->encodedToken($tokens, $encoder, 'claims-subchain'),
            'outcome_coordinate_token' => $this->encodedToken($tokens, $encoder, 'claims-outcome'),
        ]);
        $outcome = [
            'field_contract' => 'patient.dob', 'disposition' => 'mapped',
            'source_contract' => 'P2F-SOURCE-1', 'transformation_version' => 'synthetic-v1',
            'query_evidence' => str_repeat('a', 64), 'result_evidence' => str_repeat('b', 64),
            'source_token_reference' => ProtectedToken::parse($provenanceTokens['protected_source_token']['encoded_token'])->lookupDigest(),
            'root_reference' => ProtectedToken::parse($provenanceTokens['patient_root_token']['encoded_token'])->lookupDigest(),
            'subchain_reference' => ProtectedToken::parse($provenanceTokens['subchain_token']['encoded_token'])->lookupDigest(),
            'outcome_token_reference' => ProtectedToken::parse($provenanceTokens['outcome_coordinate_token']['encoded_token'])->lookupDigest(),
            'run_id' => (int) $run->id, 'source_snapshot_id' => (int) $source->id, 'target_snapshot_id' => null,
        ];
        $provenance = (new ProtectedEvidenceRepository($security))->appendProvenanceProtected(
            $this->context(['write'], (int) $run->id, (int) $source->id),
            [
                'run_id' => (int) $run->id, 'source_snapshot_id' => (int) $source->id, 'domain' => 'migration_run',
                'protected_source_token' => $outcome['source_token_reference'],
                'patient_root_token' => $outcome['root_reference'], 'subchain_token' => $outcome['subchain_reference'],
                'outcome_coordinate_token' => $outcome['outcome_token_reference'],
                'source_query_id' => 'P2F-SOURCE-1', 'query_hash' => str_repeat('a', 64), 'target_outcome' => 'mapped',
                'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
                'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1', 'state' => 'recorded',
                'encrypted_field_dispositions' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
                'integrity_checksum' => EnvelopeVerifiedIntegrityAuthority::claimHash('patient_provenance', $outcome),
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
            ],
            $provenanceTokens,
        );
        $provenanceAuthority = EnvelopeVerifiedIntegrityAuthority::provenance(
            $security,
            $this->context(['read'], (int) $run->id, (int) $source->id, null, []),
            (int) $provenance->id,
        );
        $outcome['integrity_authority'] = $provenanceAuthority;
        (new ProvenanceCompletenessValidator)->assertPatientFieldsComplete(
            ['patient.dob'], [$outcome],
            [],
            (int) $run->id, (int) $source->id, null,
        );
        $this->addToAssertionCount(1);

        $replayed = $outcome;
        $replayed['integrity_authority'] = $authority;
        $this->expectExceptionMessage('LM-SEC-PROVENANCE-INTEGRITY-AUTHORITY-001');
        (new ProvenanceCompletenessValidator)->assertPatientFieldsComplete(
            ['patient.dob'], [$replayed], [], (int) $run->id, (int) $source->id, null,
        );
    }

    public function test_crosswalk_revocation_and_quarantine_lifecycle_reseal_verified_lineage(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $bundleToken = $this->encodedToken($tokens, $encoder, 'lineage-bundle');
        $bundle = (new RunManifestRepository(security: $security))->appendContractBundleProtected(
            $this->context(['write']), $this->bundleAttributes($bundleToken), $this->tokenSet(['bundle_token' => $bundleToken]),
        );
        $runTokens = $this->tokenSet([
            'run_token' => $this->encodedToken($tokens, $encoder, 'lineage-run'),
            'cohort_token' => $this->encodedToken($tokens, $encoder, 'lineage-cohort'),
        ]);
        $run = (new RunManifestRepository(security: $security))->createRunProtected(
            $this->context(['write']), $this->runAttributes((int) $bundle->id, $runTokens), $runTokens,
        );
        $sourceTokens = $this->tokenSet([
            'snapshot_token' => $this->encodedToken($tokens, $encoder, 'lineage-source'),
            'coordinate_token' => $this->encodedToken($tokens, $encoder, 'lineage-coordinate'),
        ]);
        $source = (new SnapshotRepository($security))->appendProtected(
            $this->context(['write'], (int) $run->id),
            $this->snapshotAttributes((int) $run->id, 'source', $sourceTokens),
            $sourceTokens,
        );

        $crosswalkTokens = $this->tokenSet([
            'protected_source_token' => $this->encodedToken($tokens, $encoder, 'crosswalk-source'),
            'active_coordinate_token' => $this->encodedToken($tokens, $encoder, 'crosswalk-coordinate'),
            'idempotency_token' => $this->encodedToken($tokens, $encoder, 'crosswalk-idempotency'),
        ]);
        $crosswalk = (new CrosswalkRepository($security))->activateProtected(
            $this->context(['write'], (int) $run->id, (int) $source->id),
            $this->crosswalkAttributes((int) $run->id, (int) $source->id, $crosswalkTokens),
            $crosswalkTokens,
        );
        $updatedBy = $this->encodedToken($tokens, $encoder, 'crosswalk-revoker');
        $revoked = (new CrosswalkRepository($security))->revokeProtected(
            $this->context(['transition'], (int) $run->id, (int) $source->id, null, ['state', 'is_active']),
            (int) $crosswalk->id,
            0,
            $updatedBy,
            hash('sha256', 'SYNTHETIC-ONLY-P3B::crosswalk-revocation'),
            'migration_run',
        );
        $this->assertSame(['state' => 'revoked', 'is_active' => false], $revoked->fields);
        $this->assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', Crosswalk::class)->count());

        $rootTokens = $this->tokenSet([
            'root_token' => $this->encodedToken($tokens, $encoder, 'quarantine-root'),
            'chain_coordinate_token' => $this->encodedToken($tokens, $encoder, 'quarantine-chain'),
            'manual_review_owner_token' => $this->encodedToken($tokens, $encoder, 'quarantine-owner'),
        ]);
        $primaryTokens = $this->tokenSet([
            'idempotency_token' => $this->encodedToken($tokens, $encoder, 'quarantine-primary'),
        ]);
        $releaseGuard = new class implements QuarantineReleaseGuard
        {
            public function assertReleaseAuthorized(QuarantineRoot $root, string $approvalToken, string $revalidationChecksum): void {}
        };
        $quarantine = new QuarantineRepository($releaseGuard, $security);
        $root = $quarantine->createRootProtected(
            $this->context(['write'], (int) $run->id, (int) $source->id),
            $this->quarantineRootAttributes((int) $run->id, (int) $source->id, $rootTokens),
            $this->quarantineExceptionAttributes('SYNTHETIC-PRIMARY', 1, $primaryTokens),
            $rootTokens,
            $primaryTokens,
        );
        $secondaryTokens = $this->tokenSet([
            'idempotency_token' => $this->encodedToken($tokens, $encoder, 'quarantine-secondary'),
        ]);
        $secondary = $quarantine->appendSecondaryProtected(
            $this->context(['read', 'write'], (int) $run->id, (int) $source->id, null, []),
            $root,
            $this->quarantineExceptionAttributes('SYNTHETIC-SECONDARY', 2, $secondaryTokens),
            $secondaryTokens,
        );
        $this->assertGreaterThan(0, $secondary->id);
        $released = $quarantine->releaseProtected(
            $this->context(['transition'], (int) $run->id, (int) $source->id, null, ['current_disposition']),
            (int) $root->id,
            0,
            $this->encodedToken($tokens, $encoder, 'quarantine-approval'),
            hash('sha256', 'SYNTHETIC-ONLY-P3B::quarantine-revalidated'),
            'migration_run',
        );
        $this->assertSame(['current_disposition' => 'released'], $released->fields);
        $this->assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', QuarantineRoot::class)->count());

    }

    public function test_every_non_null_token_field_requires_a_full_envelope_entry(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $primary = $tokens->tokenize(new TokenDomain('migration_run'), $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::PRIMARY')]))->encode();
        $actor = $tokens->tokenize(new TokenDomain('migration_run'), $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::ACTOR')]))->encode();
        $attributes = $this->bundleAttributes($primary);
        $attributes['created_by_token'] = ProtectedToken::parse($actor)->lookupDigest();
        $bundle = (new RunManifestRepository)->appendContractBundle($attributes);

        $this->expectExceptionMessage('LM-SEC-STORE-TOKEN-SET-COMPLETE-001');
        $security->seal($this->context(['write']), $bundle, $primary, 'migration_run', null, null, null, 'protected', 'migration-lineage');
    }

    public function test_caller_declared_purpose_and_authority_cannot_override_the_pinned_policy(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $token = $this->encodedToken($tokens, $encoder, 'unauthorized-context');
        $bundle = (new RunManifestRepository)->appendContractBundle($this->bundleAttributes($token));
        $context = new ProtectedStoreOperationContext(
            'synthetic_repository_test',
            ['write'],
            ['migration_run'],
            'testing',
            null,
            null,
            null,
            'protected',
            'migration-lineage',
            'CALLER-SELF-ASSERTED-AUTHORITY',
        );

        $this->expectExceptionMessage('LM-SEC-STORE-AUTHORITY-CONTEXT-001');
        $security->seal($context, $bundle, $token, 'migration_run', null, null, null, 'protected', 'migration-lineage');
    }

    public function test_contract_bundle_reuse_requires_exact_version_hash_and_verified_envelope(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $repository = new RunManifestRepository(security: $security);
        $token = $this->encodedToken($tokens, $encoder, 'bundle-reuse');
        $tokenSet = $this->tokenSet(['bundle_token' => $token]);
        $attributes = $this->bundleAttributes($token);
        $bundle = $repository->appendContractBundleProtected($this->context(['write']), $attributes, $tokenSet);

        $resolved = $repository->resolveContractBundleProtected(
            $this->context(['read'], fields: ['bundle_version']),
            (string) $attributes['bundle_version'],
            (string) $attributes['bundle_hash'],
        );
        $this->assertSame((int) $bundle->id, $resolved?->recordId);

        $conflictToken = $this->encodedToken($tokens, $encoder, 'bundle-conflict');
        $conflictTokenSet = $this->tokenSet(['bundle_token' => $conflictToken]);
        $repository->appendContractBundleProtected(
            $this->context(['write']),
            array_replace($this->bundleAttributes($conflictToken), ['bundle_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::conflict')]),
            $conflictTokenSet,
        );

        $this->expectException(StorageIntegrityException::class);
        $repository->resolveContractBundleProtected(
            $this->context(['read'], fields: ['bundle_version']),
            (string) $attributes['bundle_version'],
            (string) $attributes['bundle_hash'],
        );
    }

    public function test_every_legacy_unsealed_repository_entry_point_is_permanently_test_only(): void
    {
        $original = app()->environment();
        app()->detectEnvironment(static fn (): string => 'local');
        $model = new MigrationRun;
        $crosswalk = new Crosswalk;
        $root = new QuarantineRoot;
        $idempotency = new IdempotencyRecord;
        $intent = new AtomicIntent;
        $reservation = new NumberReservation;
        $cases = [
            fn () => (new RunManifestRepository)->appendContractBundle([]),
            fn () => (new RunManifestRepository)->createRun([]),
            fn () => (new RunManifestRepository)->findByToken(''),
            fn () => (new RunManifestRepository)->advance($model, 'NOT_STARTED', 0, 'EXTRACTED'),
            fn () => (new SnapshotRepository)->append([]),
            fn () => (new SnapshotRepository)->appendTargetCollision([]),
            fn () => (new CrosswalkRepository)->activate([]),
            fn () => (new CrosswalkRepository)->resolveActive('', '', ''),
            fn () => (new CrosswalkRepository)->revoke($crosswalk, 0, '', ''),
            fn () => (new ProtectedEvidenceRepository)->appendRemediation([]),
            fn () => (new ProtectedEvidenceRepository)->appendProvenance([]),
            fn () => (new QuarantineRepository)->createRoot([], []),
            fn () => (new QuarantineRepository)->appendSecondary($root, []),
            fn () => (new QuarantineRepository)->release($root, 0, '', ''),
            fn () => (new ReconciliationRepository)->append([]),
            fn () => (new MigrationAuditRepository)->append([]),
            fn () => (new IdempotencyRepository)->resolveOrCreate([]),
            fn () => (new IdempotencyRepository)->advance($idempotency, 'NOT_STARTED', 0, 'EXTRACTED'),
            fn () => (new RecoveryRepository)->createIntent([]),
            fn () => (new RecoveryRepository)->advanceIntent($intent, 'CORE_COMMITTING', 0, 'CORE_COMMITTED'),
            fn () => (new RecoveryRepository)->appendCheckpoint([]),
            fn () => (new RecoveryRepository)->appendCompensation([]),
            fn () => (new NumberReservationRepository)->reserve([]),
            fn () => (new NumberReservationRepository)->classifyConsumption($reservation, 0, 'committed', 'committed', ''),
            fn () => (new CompareAndSet)->state($model, 'NOT_STARTED', 0, 'EXTRACTED'),
        ];

        try {
            foreach ($cases as $case) {
                try {
                    $case();
                    $this->fail('A legacy unsealed repository entry point was available outside isolated SQLite tests.');
                } catch (ProtectedStoreAccessDeniedException $exception) {
                    $this->assertStringContainsString('LM-SEC-STORE-LEGACY-UNSEALED-METHOD-001', $exception->getMessage());
                }
            }
        } finally {
            app()->detectEnvironment(static fn (): string => $original);
        }
    }

    public function test_run_snapshot_and_reconciliation_secure_paths_pin_coordinates_and_return_projections(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $bundleToken = $this->encodedToken($tokens, $encoder, 'bundle');
        $bundleTokens = $this->tokenSet(['bundle_token' => $bundleToken]);
        $bundle = (new RunManifestRepository(security: $security))->appendContractBundleProtected(
            $this->context(['write']),
            $this->bundleAttributes($bundleToken),
            $bundleTokens,
        );

        $runToken = $this->encodedToken($tokens, $encoder, 'run');
        $cohortToken = $this->encodedToken($tokens, $encoder, 'cohort');
        $runTokens = $this->tokenSet(['run_token' => $runToken, 'cohort_token' => $cohortToken]);
        $run = (new RunManifestRepository(security: $security))->createRunProtected(
            $this->context(['write']),
            $this->runAttributes((int) $bundle->id, $runTokens),
            $runTokens,
        );
        $runProjection = (new RunManifestRepository(security: $security))->findByTokenProtected(
            $this->context(['read'], (int) $run->id, null, null, ['state']),
            $runToken,
        );
        $this->assertSame(['state' => 'NOT_STARTED'], $runProjection?->fields);
        $transition = (new RunManifestRepository(security: $security))->advanceProtected(
            $this->context(['transition'], (int) $run->id, null, null, ['state']),
            (int) $run->id,
            'NOT_STARTED',
            0,
            'EXTRACTED',
        );
        $this->assertSame(['state' => 'EXTRACTED'], $transition->fields);

        $snapshotRepository = new SnapshotRepository($security);
        $sourceTokens = $this->tokenSet([
            'snapshot_token' => $this->encodedToken($tokens, $encoder, 'source-snapshot'),
            'coordinate_token' => $this->encodedToken($tokens, $encoder, 'source-coordinate'),
        ]);
        $source = $snapshotRepository->appendProtected(
            $this->context(['write'], (int) $run->id),
            $this->snapshotAttributes((int) $run->id, 'source', $sourceTokens),
            $sourceTokens,
        );
        $targetTokens = $this->tokenSet([
            'snapshot_token' => $this->encodedToken($tokens, $encoder, 'target-snapshot'),
            'coordinate_token' => $this->encodedToken($tokens, $encoder, 'target-coordinate'),
        ]);
        $target = $snapshotRepository->appendProtected(
            $this->context(['write'], (int) $run->id),
            $this->snapshotAttributes((int) $run->id, 'target', $targetTokens),
            $targetTokens,
        );
        $sourceProjection = $snapshotRepository->readProtected(
            $this->context(['read'], (int) $run->id, (int) $source->id, null, ['snapshot_kind']),
            (int) $source->id,
        );
        $this->assertSame(['snapshot_kind' => 'source'], $sourceProjection->fields);

        $reconTokens = $this->tokenSet([
            'cohort_token' => $cohortToken,
            'idempotency_token' => $this->encodedToken($tokens, $encoder, 'reconciliation-idempotency'),
        ]);
        $reconciliation = (new ReconciliationRepository($security))->appendProtected(
            $this->context(['write'], (int) $run->id, (int) $source->id, (int) $target->id),
            $this->reconciliationAttributes((int) $run->id, (int) $source->id, (int) $target->id, $reconTokens),
            $reconTokens,
        );

        $projection = (new ReconciliationRepository($security))->readProtected(
            $this->context(['read'], (int) $run->id, (int) $source->id, (int) $target->id, ['contract_id']),
            (int) $reconciliation->id,
        );
        $this->assertSame(['contract_id' => 'SYNTHETIC-RECON-001'], $projection->fields);
        $this->assertSame(6, DB::table('legacy_migration_protected_record_envelopes')->count());
        $this->assertSame(9, DB::table('legacy_migration_protected_access_audits')->count());
    }

    public function test_idempotency_recovery_and_reservation_paths_require_envelopes_and_reseal_every_transition(): void
    {
        [$security, $tokens, $encoder] = $this->security();
        $bundleToken = $this->encodedToken($tokens, $encoder, 'lifecycle-bundle');
        $bundleTokens = $this->tokenSet(['bundle_token' => $bundleToken]);
        $bundle = (new RunManifestRepository(security: $security))->appendContractBundleProtected(
            $this->context(['write']),
            $this->bundleAttributes($bundleToken),
            $bundleTokens,
        );
        $runTokens = $this->tokenSet([
            'run_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-run'),
            'cohort_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-cohort'),
        ]);
        $run = (new RunManifestRepository(security: $security))->createRunProtected(
            $this->context(['write']),
            $this->runAttributes((int) $bundle->id, $runTokens),
            $runTokens,
        );
        $runTransition = (new CompareAndSet($security))->stateProtected(
            $this->context(['transition'], (int) $run->id, null, null, ['state']),
            MigrationRun::class,
            (int) $run->id,
            'NOT_STARTED',
            0,
            'EXTRACTED',
        );
        $this->assertSame(['state' => 'EXTRACTED'], $runTransition->fields);
        $snapshotRepository = new SnapshotRepository($security);
        $sourceTokens = $this->tokenSet([
            'snapshot_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-source'),
            'coordinate_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-source-coordinate'),
        ]);
        $source = $snapshotRepository->appendProtected(
            $this->context(['write'], (int) $run->id),
            $this->snapshotAttributes((int) $run->id, 'source', $sourceTokens),
            $sourceTokens,
        );
        $targetTokens = $this->tokenSet([
            'snapshot_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-target'),
            'coordinate_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-target-coordinate'),
        ]);
        $target = $snapshotRepository->appendProtected(
            $this->context(['write'], (int) $run->id),
            $this->snapshotAttributes((int) $run->id, 'target', $targetTokens),
            $targetTokens,
        );

        $idempotencyTokens = $this->tokenSet([
            'protected_source_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-source-row'),
            'idempotency_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-idempotency'),
        ]);
        $idempotencyRepository = new IdempotencyRepository(security: $security);
        $idempotency = $idempotencyRepository->resolveOrCreateProtected(
            $this->context(['write', 'read'], (int) $run->id, (int) $source->id, (int) $target->id, ['state']),
            $this->idempotencyAttributes((int) $run->id, (int) $source->id, (int) $target->id, $idempotencyTokens),
            $idempotencyTokens,
        );
        $this->assertSame(['state' => 'NOT_STARTED'], $idempotency->fields);
        $advancedIdempotency = $idempotencyRepository->advanceProtected(
            $this->context(['transition'], (int) $run->id, (int) $source->id, (int) $target->id, ['state']),
            $idempotency->recordId,
            'NOT_STARTED',
            0,
            'EXTRACTED',
            ['integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::idempotency-advanced')],
        );
        $this->assertSame(['state' => 'EXTRACTED'], $advancedIdempotency->fields);

        $intentTokens = $this->tokenSet([
            'intent_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-intent'),
        ]);
        $recoveryRepository = new RecoveryRepository(security: $security);
        $intent = $recoveryRepository->createIntentProtected(
            $this->context(['write'], (int) $run->id),
            $this->intentAttributes((int) $run->id, $idempotency->recordId, $intentTokens),
            $intentTokens,
        );
        $advancedIntent = $recoveryRepository->advanceIntentProtected(
            $this->context(['transition'], (int) $run->id, null, null, ['state']),
            (int) $intent->id,
            'CORE_COMMITTING',
            0,
            'CORE_COMMITTED',
            ['integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::intent-advanced')],
        );
        $this->assertSame(['state' => 'CORE_COMMITTED'], $advancedIntent->fields);

        $checkpointTokens = $this->tokenSet([
            'dependency_chain_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-dependency'),
        ]);
        $checkpoint = $recoveryRepository->appendCheckpointProtected(
            $this->context(['write'], (int) $run->id),
            $this->checkpointAttributes((int) $run->id, $idempotency->recordId, (int) $intent->id, $checkpointTokens),
            $checkpointTokens,
        );
        $checkpointProjection = $recoveryRepository->readProtected(
            $this->context(['read'], (int) $run->id, null, null, ['stage']),
            Checkpoint::class,
            (int) $checkpoint->id,
        );
        $this->assertSame(['stage' => 'CORE_COMMITTED'], $checkpointProjection->fields);

        $compensationTokens = $this->tokenSet([
            'compensation_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-compensation'),
        ]);
        $compensation = $recoveryRepository->appendCompensationProtected(
            $this->context(['write'], (int) $run->id),
            $this->compensationAttributes((int) $run->id, (int) $intent->id, $compensationTokens),
            $compensationTokens,
        );
        $compensationProjection = $recoveryRepository->readProtected(
            $this->context(['read'], (int) $run->id, null, null, ['action_type']),
            CompensationRecord::class,
            (int) $compensation->id,
        );
        $this->assertSame(['action_type' => 'repair_unit_a_metadata'], $compensationProjection->fields);

        $reservationTokens = $this->tokenSet([
            'protected_source_token' => $idempotencyTokens['protected_source_token']['encoded_token'],
            'numbering_coordinate_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-number-coordinate'),
            'protected_number_token' => $this->encodedToken($tokens, $encoder, 'lifecycle-number'),
        ]);
        $reservationRepository = new NumberReservationRepository($security, new class implements ReservationCoordinateContextFactory
        {
            public function idempotencyContext(int $idempotencyRecordId, int $runId, int $sourceSnapshotId, int $targetSnapshotId): ProtectedStoreOperationContext
            {
                return new ProtectedStoreOperationContext(
                    'synthetic_repository_test', ['read'], ['migration_run'], 'testing', $runId, $sourceSnapshotId,
                    $targetSnapshotId, 'protected', 'migration-lineage', 'SYNTHETIC-AUTHORITY',
                    ['run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain'],
                );
            }
        });
        $reservation = $reservationRepository->reserveProtected(
            $this->context(['write', 'read'], (int) $run->id, null, null, ['state']),
            $this->reservationAttributes((int) $run->id, $idempotency->recordId, $reservationTokens),
            $reservationTokens,
        );
        $this->assertSame(['state' => 'reserved'], $reservation->fields);
        $classified = $reservationRepository->classifyConsumptionProtected(
            $this->context(['transition'], (int) $run->id, null, null, ['state', 'consumption_classification']),
            $reservation->recordId,
            0,
            'committed',
            'committed',
            hash('sha256', 'SYNTHETIC-ONLY-P3B::reservation-committed'),
        );
        $this->assertSame('committed', $classified->fields['consumption_classification']);

        $mismatchedReservationRepository = new NumberReservationRepository(
            $security,
            connection: 'synthetic_mismatched_connection',
        );
        $beforeMismatchState = DB::table('legacy_migration_number_reservations')->where('id', $reservation->recordId)->value('state');
        $beforeMismatchEnvelopes = DB::table('legacy_migration_protected_record_envelopes')->count();
        $beforeMismatchAudits = DB::table('legacy_migration_protected_access_audits')->count();
        foreach (['verify', 'classify'] as $operation) {
            try {
                $operation === 'verify'
                    ? $mismatchedReservationRepository->verifyProtected($this->context(['read']), $reservation->recordId)
                    : $mismatchedReservationRepository->classifyConsumptionProtected(
                        $this->context(['transition']), $reservation->recordId, 1, 'explained', 'explained', hash('sha256', 'SYNTHETIC-MISMATCH'),
                    );
                self::fail("A mismatched reservation {$operation} crossed the protected connection boundary.");
            } catch (ProtectedStoreAccessDeniedException $exception) {
                self::assertStringContainsString('LM-SEC-RESERVATION-CONNECTION-MISMATCH-001', $exception->getMessage());
            }
        }
        self::assertSame($beforeMismatchState, DB::table('legacy_migration_number_reservations')->where('id', $reservation->recordId)->value('state'));
        self::assertSame($beforeMismatchEnvelopes, DB::table('legacy_migration_protected_record_envelopes')->count());
        self::assertSame($beforeMismatchAudits, DB::table('legacy_migration_protected_access_audits')->count());

        $deniedBeforeTamper = DB::table('legacy_migration_protected_access_audits')->where('result_code', 'denied_or_tampered')->count();
        $tamperedEnvelope = (array) DB::table('legacy_migration_protected_record_envelopes')
            ->where('record_type', IdempotencyRecord::class)->where('record_id', $idempotency->recordId)
            ->orderByDesc('generation')->first();
        unset($tamperedEnvelope['id']);
        $tamperedEnvelope['generation'] = ((int) $tamperedEnvelope['generation']) + 1;
        $tamperedEnvelope['encrypted_integrity_seal'] = 'SYNTHETIC-INVALID-ENVELOPE-SEAL';
        DB::table('legacy_migration_protected_record_envelopes')->insert($tamperedEnvelope);
        try {
            $reservationRepository->reserveProtected(
                $this->context(['write', 'read'], (int) $run->id, null, null, ['state']),
                $this->reservationAttributes((int) $run->id, $idempotency->recordId, $reservationTokens),
                $reservationTokens,
            );
            self::fail('A raw-tampered idempotency parent authorized a direct reservation call.');
        } catch (\Throwable $exception) {
            self::assertNotSame('', $exception->getMessage());
        }
        self::assertSame($deniedBeforeTamper + 1, DB::table('legacy_migration_protected_access_audits')->where('result_code', 'denied_or_tampered')->count());
        $unsealedTokens = $this->tokenSet([
            'protected_source_token' => $this->encodedToken($tokens, $encoder, 'unsealed-parent-source'),
            'idempotency_token' => $this->encodedToken($tokens, $encoder, 'unsealed-parent-idempotency'),
        ]);
        $unsealedAttributes = $this->idempotencyAttributes((int) $run->id, (int) $source->id, (int) $target->id, $unsealedTokens);
        $unsealedId = DB::table('legacy_migration_idempotency_records')->insertGetId($unsealedAttributes + ['created_at' => now(), 'updated_at' => now()]);
        $unsealedReservationTokens = $this->tokenSet([
            'protected_source_token' => $unsealedTokens['protected_source_token']['encoded_token'],
            'numbering_coordinate_token' => $this->encodedToken($tokens, $encoder, 'unsealed-parent-coordinate'),
            'protected_number_token' => $this->encodedToken($tokens, $encoder, 'unsealed-parent-number'),
        ]);
        try {
            $reservationRepository->reserveProtected(
                $this->context(['write', 'read'], (int) $run->id, null, null, ['state']),
                $this->reservationAttributes((int) $run->id, (int) $unsealedId, $unsealedReservationTokens),
                $unsealedReservationTokens,
            );
            self::fail('An unsealed idempotency parent authorized a direct reservation call.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            self::assertStringContainsString('LM-SEC-STORE-ENVELOPE-MISSING-001', $exception->getMessage());
        }
        self::assertSame(0, DB::table('legacy_migration_number_reservations')->where('idempotency_record_id', $unsealedId)->count());

        $this->assertSame(3, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', IdempotencyRecord::class)->count());
        $this->assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', AtomicIntent::class)->count());
        $this->assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', NumberReservation::class)->count());
        $this->assertSame(0, DB::table('legacy_migration_protected_record_envelopes')->whereNull('encrypted_token_set')->count());
    }

    /** @return array{ProtectedRecordSecurityRepository, HmacTokenService, CanonicalTypedMessageEncoder, ProtectedRecordEnvelopeFactory, ProtectedRecordEnvelopeVerifier} */
    private function security(string $version = 'v1', string $key = '56789abcdef01234FABCDE!@#$%^&*()-+=01234'): array
    {
        $versions = new CanonicalizationVersionRegistry;
        $encoder = new CanonicalTypedMessageEncoder($versions);
        $tokens = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => $version,
                'key' => $key,
                'algorithm' => 'sha256',
            ]),
            $versions,
            'testing',
            new TokenDomainRegistry(['migration_run', 'artifact_integrity']),
        );
        $guard = new ProtectedStoreAccessGuard(
            new PinnedProtectedStoreAccessAuthority(
                'testing',
                ['migration_run'],
                'migration-hmac',
                $version,
                'typed-length-prefix/1',
                operationPolicies: [
                    'synthetic_repository_test' => [
                        'operations' => ['write', 'read', 'rotate', 'transition'],
                        'authority_references' => ['SYNTHETIC-AUTHORITY'],
                        'access_classifications' => ['protected'],
                        'retention_classifications' => ['migration-lineage'],
                    ],
                ],
            ),
            $tokens,
            $encoder,
        );

        $factory = new ProtectedRecordEnvelopeFactory($guard);
        $verifier = new ProtectedRecordEnvelopeVerifier($guard);

        return [
            new ProtectedRecordSecurityRepository($factory, $verifier),
            $tokens,
            $encoder,
            $factory,
            $verifier,
        ];
    }

    /** @param list<string> $operations */
    private function context(array $operations, ?int $runId = null, ?int $sourceSnapshotId = null, ?int $targetSnapshotId = null, array $fields = ['bundle_version']): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext('synthetic_repository_test', $operations, ['migration_run'], 'testing', $runId, $sourceSnapshotId, $targetSnapshotId, 'protected', 'migration-lineage', 'SYNTHETIC-AUTHORITY', $fields);
    }

    /** @return array<string, mixed> */
    private function bundleAttributes(?string $encodedToken = null): array
    {
        return [
            'bundle_token' => $encodedToken === null
                ? hash('sha256', 'SYNTHETIC-ONLY-P3B::bundle')
                : ProtectedToken::parse($encodedToken)->lookupDigest(),
            'bundle_version' => 'SYNTHETIC-P3B-1',
            'bundle_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::bundle-hash'),
            'canonicalization_version' => 'typed-length-prefix/1',
            'token_environment' => 'testing',
            'hmac_key_id' => 'migration-hmac',
            'hmac_key_version' => 'v1',
            'encrypted_manifest' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::integrity'),
            'access_classification' => 'protected',
            'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function runAttributes(int $bundleId, array $tokens): array
    {
        return [
            'run_token' => ProtectedToken::parse($tokens['run_token']['encoded_token'])->lookupDigest(),
            'cohort_token' => ProtectedToken::parse($tokens['cohort_token']['encoded_token'])->lookupDigest(),
            'contract_bundle_id' => $bundleId,
            'mode' => 'dry_run', 'state' => 'NOT_STARTED',
            'configuration_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::config'),
            'source_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::source'),
            'target_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::target'),
            'canonicalization_version' => 'typed-length-prefix/1', 'token_environment' => 'testing',
            'hmac_key_id' => 'migration-hmac', 'hmac_key_version' => 'v1',
            'encrypted_manifest' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'manifest_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::run-integrity'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function snapshotAttributes(int $runId, string $kind, array $tokens): array
    {
        return [
            'run_id' => $runId,
            'snapshot_token' => ProtectedToken::parse($tokens['snapshot_token']['encoded_token'])->lookupDigest(),
            'snapshot_kind' => $kind, 'domain' => 'patient',
            'coordinate_token' => ProtectedToken::parse($tokens['coordinate_token']['encoded_token'])->lookupDigest(),
            'database_fingerprint' => hash('sha256', "SYNTHETIC-ONLY-P3B::{$kind}-db"),
            'schema_fingerprint' => hash('sha256', "SYNTHETIC-ONLY-P3B::{$kind}-schema"),
            'query_bundle_hash' => hash('sha256', "SYNTHETIC-ONLY-P3B::{$kind}-query"),
            'result_hash' => hash('sha256', "SYNTHETIC-ONLY-P3B::{$kind}-result"),
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'state' => 'captured', 'encrypted_metadata' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'integrity_checksum' => hash('sha256', "SYNTHETIC-ONLY-P3B::{$kind}-integrity"),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
            'captured_at' => now(),
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function reconciliationAttributes(int $runId, int $sourceId, int $targetId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'source_snapshot_id' => $sourceId, 'target_snapshot_id' => $targetId,
            'cohort_token' => ProtectedToken::parse($tokens['cohort_token']['encoded_token'])->lookupDigest(),
            'domain' => 'patient', 'contract_id' => 'SYNTHETIC-RECON-001',
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'mandatory' => true, 'measurement_complete' => true, 'difference' => '0', 'tolerance' => '0',
            'acceptance_result' => 'passed', 'state' => 'RECONCILIATION_PASSED',
            'evidence_bundle_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::evidence'),
            'idempotency_token' => ProtectedToken::parse($tokens['idempotency_token']['encoded_token'])->lookupDigest(),
            'encrypted_population_definition' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'encrypted_expected_equation' => ['left' => 1, 'right' => 1],
            'encrypted_measured_values' => ['left' => 1, 'right' => 1],
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::recon-integrity'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function crosswalkAttributes(int $runId, int $sourceId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'source_snapshot_id' => $sourceId, 'target_snapshot_id' => null,
            'domain' => 'migration_run',
            'protected_source_token' => ProtectedToken::parse($tokens['protected_source_token']['encoded_token'])->lookupDigest(),
            'active_coordinate_token' => ProtectedToken::parse($tokens['active_coordinate_token']['encoded_token'])->lookupDigest(),
            'branch' => 'create_new', 'state' => 'active', 'patient_number_action' => 'none',
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'idempotency_token' => ProtectedToken::parse($tokens['idempotency_token']['encoded_token'])->lookupDigest(),
            'encrypted_mapping_payload' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::crosswalk'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function quarantineRootAttributes(int $runId, int $sourceId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'source_snapshot_id' => $sourceId, 'parent_root_id' => null,
            'root_domain' => 'migration_run',
            'root_token' => ProtectedToken::parse($tokens['root_token']['encoded_token'])->lookupDigest(),
            'chain_coordinate_token' => ProtectedToken::parse($tokens['chain_coordinate_token']['encoded_token'])->lookupDigest(),
            'blocking_scope' => 'patient_chain', 'current_disposition' => 'held',
            'manual_review_owner_token' => ProtectedToken::parse($tokens['manual_review_owner_token']['encoded_token'])->lookupDigest(),
            'topological_rank' => 1,
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'state' => 'held', 'encrypted_release_conditions' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::quarantine-root'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function quarantineExceptionAttributes(string $code, int $ordinal, array $tokens): array
    {
        return [
            'exception_code' => $code, 'precedence_ordinal' => $ordinal,
            'rule_version' => 'synthetic-v1', 'evidence_version' => 'synthetic-v1',
            'idempotency_token' => ProtectedToken::parse($tokens['idempotency_token']['encoded_token'])->lookupDigest(),
            'state' => 'active', 'encrypted_evidence' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::'.$code),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function idempotencyAttributes(int $runId, int $sourceId, int $targetId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'source_snapshot_id' => $sourceId, 'target_snapshot_id' => $targetId,
            'domain' => 'patient_core',
            'protected_source_token' => ProtectedToken::parse($tokens['protected_source_token']['encoded_token'])->lookupDigest(),
            'idempotency_token' => ProtectedToken::parse($tokens['idempotency_token']['encoded_token'])->lookupDigest(),
            'outcome_type' => 'patient_core', 'input_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::input'),
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'state' => 'NOT_STARTED', 'attempt_count' => 0,
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::idempotency'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function intentAttributes(int $runId, int $idempotencyId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'idempotency_record_id' => $idempotencyId, 'domain' => 'patient_core',
            'intent_token' => ProtectedToken::parse($tokens['intent_token']['encoded_token'])->lookupDigest(),
            'unit_name' => 'A_PATIENT_CORE', 'expected_prior_state' => 'CLASSIFIED',
            'state' => 'CORE_COMMITTING', 'attempt' => 1,
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::intent'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function checkpointAttributes(int $runId, int $idempotencyId, int $intentId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'idempotency_record_id' => $idempotencyId, 'atomic_intent_id' => $intentId,
            'domain' => 'patient_core',
            'dependency_chain_token' => ProtectedToken::parse($tokens['dependency_chain_token']['encoded_token'])->lookupDigest(),
            'stage' => 'CORE_COMMITTED', 'attempt_contract_version' => 'SYNTHETIC-P3B-1',
            'expected_prior_state' => 'CORE_COMMITTING', 'state' => 'CORE_COMMITTED',
            'input_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::checkpoint-input'),
            'output_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::checkpoint-output'),
            'transaction_evidence_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::transaction'),
            'write_set_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::writes'),
            'reconciliation_bundle_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::checkpoint-reconciliation'),
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::checkpoint'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function reservationAttributes(int $runId, int $idempotencyId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'idempotency_record_id' => $idempotencyId, 'domain' => 'patient_number',
            'protected_source_token' => ProtectedToken::parse($tokens['protected_source_token']['encoded_token'])->lookupDigest(),
            'numbering_coordinate_token' => ProtectedToken::parse($tokens['numbering_coordinate_token']['encoded_token'])->lookupDigest(),
            'sequence_ordinal' => 1,
            'protected_number_token' => ProtectedToken::parse($tokens['protected_number_token']['encoded_token'])->lookupDigest(),
            'configuration_fingerprint' => hash('sha256', 'SYNTHETIC-ONLY-P3B::number-config'),
            'period_key' => '2026', 'timezone' => 'UTC', 'state' => 'reserved',
            'consumption_classification' => 'explained',
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'encrypted_number_payload' => ['fixture' => 'SYNTHETIC-ONLY-P3B'],
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::reservation'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    /** @param array<string,array{encoded_token:string,domain:string}> $tokens @return array<string,mixed> */
    private function compensationAttributes(int $runId, int $intentId, array $tokens): array
    {
        return [
            'run_id' => $runId, 'atomic_intent_id' => $intentId, 'domain' => 'patient_core',
            'compensation_token' => ProtectedToken::parse($tokens['compensation_token']['encoded_token'])->lookupDigest(),
            'unit_name' => 'A_PATIENT_CORE', 'action_type' => 'repair_unit_a_metadata',
            'recovery_classification' => 'metadata_repair', 'state' => 'COMPENSATION_REQUIRED',
            'operator_review_required' => true,
            'before_evidence_hash' => hash('sha256', 'SYNTHETIC-ONLY-P3B::compensation-before'),
            'contract_version' => 'SYNTHETIC-P3B-1', 'transformation_version' => 'synthetic-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'integrity_checksum' => hash('sha256', 'SYNTHETIC-ONLY-P3B::compensation'),
            'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
        ];
    }

    private function encodedToken(HmacTokenService $tokens, CanonicalTypedMessageEncoder $encoder, string $label): string
    {
        return $tokens->tokenize(new TokenDomain('migration_run'), $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::'.$label)]))->encode();
    }

    /** @param list<string> $domains @return array<string,mixed> */
    private function keyReference(string $version, string $secretReference, string $rotationAuthority, array $domains, bool $active): array
    {
        return [
            'key_id' => 'migration-hmac', 'version' => $version, 'secret_reference' => $secretReference,
            'environment' => 'testing', 'domains' => $domains, 'activated_at' => '2020-01-01T00:00:00Z',
            'retired_at' => null, 'verification_expires_at' => null, 'rotation_authority' => $rotationAuthority,
            'revoked' => false, 'active_for_signing' => $active,
        ];
    }

    private function forgetContainerSecurity(): void
    {
        foreach ([
            HmacTokenService::class,
            ProtectedStoreAccessAuthority::class,
            ProtectedStoreAccessGuard::class,
            ProtectedRecordEnvelopeFactory::class,
            ProtectedRecordEnvelopeVerifier::class,
            ProtectedRecordRotationAuthority::class,
            ProtectedRecordSecurityRepository::class,
        ] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
    }

    private function recoveryAuthority(string $reference): RecoveryJournalAuthority
    {
        return new class($reference) implements RecoveryJournalAuthority
        {
            public function __construct(private readonly string $reference) {}

            public function environment(): string
            {
                return 'testing';
            }

            public function authorityReference(): string
            {
                return $this->reference;
            }

            public function accessClassification(): string
            {
                return 'protected';
            }

            public function retentionClassification(): string
            {
                return 'migration-lineage';
            }

            public function contractVersion(): string
            {
                return 'synthetic-contract/1';
            }

            public function transformationVersion(): string
            {
                return 'not-authorized';
            }

            public function canonicalizationVersion(): string
            {
                return 'typed-length-prefix/1';
            }

            public function expectedContractBundleHash(): string
            {
                return str_repeat('a', 64);
            }
        };
    }

    /** @param array<string,string> $tokens @return array<string,array{encoded_token:string,domain:string}> */
    private function tokenSet(array $tokens): array
    {
        $set = [];
        foreach ($tokens as $field => $token) {
            $set[$field] = ['encoded_token' => $token, 'domain' => 'migration_run'];
        }

        return $set;
    }
}
