<?php

namespace Tests\Unit\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\ExternalReferenceKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\LegacyMigrationSecurityFactory;
use App\Services\LegacyMigration\Foundation\Security\OperationalKeyReference;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelope;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\RemediationCandidate;
use App\Services\LegacyMigration\Foundation\Security\RetentionPolicy;
use App\Services\LegacyMigration\Foundation\Security\RetentionPurgeGate;
use App\Services\LegacyMigration\Foundation\Security\SecurityConfigurationException;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TokenDomainRegistry;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Security\VerifiedIntegrityAuthority;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProtectedLifecycleSecurityTest extends TestCase
{
    #[Test]
    public function envelope_binds_purpose_environment_domain_run_snapshots_and_record_integrity(): void
    {
        [$factory, $verifier, $token] = $this->envelopeServices();
        $context = $this->context(['write', 'read']);
        $envelope = $factory->create($context, 'write', 'SyntheticRecord', 11, $token, 'patient_source', 4, 5, 6, 'protected', 'migration-lineage', str_repeat('a', 64), $this->tokenSet($token));

        $authorized = $verifier->authorize($context, 'read', $envelope);

        $this->assertSame('patient_source', $authorized->domain());
        $this->assertSame('migration-hmac', $authorized->keyId());
    }

    #[Test]
    public function envelope_rejects_cross_run_replay_and_altered_metadata(): void
    {
        [$factory, $verifier, $token] = $this->envelopeServices();
        $envelope = $factory->create($this->context(['write']), 'write', 'SyntheticRecord', 11, $token, 'patient_source', 4, 5, 6, 'protected', 'migration-lineage', str_repeat('a', 64), $this->tokenSet($token));

        try {
            $verifier->authorize($this->context(['read'], 99), 'read', $envelope);
            $this->fail('Cross-run replay was accepted.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            $this->assertStringContainsString('LM-SEC-STORE-COORDINATE-001', $exception->getMessage());
        }

        $altered = new ProtectedRecordEnvelope(
            $envelope->recordType,
            $envelope->recordId,
            $envelope->encodedToken,
            $envelope->encodedIntegritySeal,
            $envelope->tokenSet,
            $envelope->domain,
            $envelope->tokenEnvironment,
            $envelope->keyId,
            $envelope->keyVersion,
            $envelope->canonicalizationVersion,
            str_repeat('b', 64),
            $envelope->runId,
            $envelope->sourceSnapshotId,
            $envelope->targetSnapshotId,
            $envelope->accessClassification,
            $envelope->retentionClassification,
        );
        $this->expectExceptionMessage('LM-SEC-STORE-INTEGRITY-001');
        $verifier->authorize($this->context(['read']), 'read', $altered);
    }

    #[Test]
    public function external_key_references_resolve_at_use_time_and_retained_keys_are_verification_only(): void
    {
        $now = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $active = new OperationalKeyReference('migration-hmac', 'v2', 'secret://active', 'testing', ['patient_source'], $now->modify('-1 day'), null, null, 'OWNER-DIRECTIVE', false, true);
        $old = new OperationalKeyReference('migration-hmac', 'v1', 'secret://old', 'testing', ['patient_source'], $now->modify('-2 years'), $now->modify('-1 day'), $now->modify('+30 days'), 'OWNER-DIRECTIVE', false, false);
        $calls = [];
        $provider = new ExternalReferenceKeyProvider([$active, $old], 'testing', 'patient_source', function (string $reference) use (&$calls): string {
            $calls[] = $reference;

            return $reference === 'secret://active'
                ? '56789abcdef01234FABCDE!@#$%^&*()-+=active'
                : '56789abcdef01234FABCDE!@#$%^&*()-+=retained';
        }, $now);

        $this->assertSame('v2', $provider->active()->version());
        $this->assertSame('v1', $provider->get('migration-hmac', 'v1')->version());
        $this->assertSame(['secret://active', 'secret://old'], $calls);
    }

    #[Test]
    public function revoked_or_expired_verification_key_fails_closed(): void
    {
        $now = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $active = new OperationalKeyReference('migration-hmac', 'v2', 'secret://active', 'testing', ['patient_source'], $now->modify('-1 day'), null, null, 'OWNER-DIRECTIVE', false, true);
        $expired = new OperationalKeyReference('migration-hmac', 'v1', 'secret://old', 'testing', ['patient_source'], $now->modify('-2 years'), $now->modify('-1 year'), $now->modify('-1 day'), 'OWNER-DIRECTIVE', false, false);
        $provider = new ExternalReferenceKeyProvider([$active, $expired], 'testing', 'patient_source', static fn (): string => '56789abcdef01234FABCDE!@#$%^&*()-+=secret', $now);

        $this->expectException(SecurityConfigurationException::class);
        $provider->get('migration-hmac', 'v1');
    }

    #[Test]
    public function application_factory_uses_external_reference_metadata_without_caching_resolved_key_material(): void
    {
        $calls = [];
        $service = LegacyMigrationSecurityFactory::make([
            'versions' => ['canonicalization' => 'typed-length-prefix/1'],
            'hmac' => ['domains' => ['patient_source', 'artifact_integrity']],
            'key_provider' => [
                'provider' => 'external_reference',
                'references' => [[
                    'key_id' => 'migration-hmac',
                    'version' => 'v2',
                    'secret_reference' => 'secret://migration/active',
                    'environment' => 'testing',
                    'domains' => ['patient_source', 'artifact_integrity'],
                    'activated_at' => '2026-07-21T00:00:00Z',
                    'rotation_authority' => 'OWNER-DIRECTIVE',
                    'revoked' => false,
                    'active_for_signing' => true,
                ]],
            ],
        ], 'testing', function (string $reference) use (&$calls): string {
            $calls[] = $reference;

            return '56789abcdef01234FABCDE!@#$%^&*()-+=runtime';
        });
        $this->assertSame([], $calls, 'Factory construction must not resolve key bytes into configuration state.');

        $token = $service->tokenize(new TokenDomain('patient_source'), (new CanonicalTypedMessageEncoder)->encode([TypedValue::string('SYNTHETIC-ONLY-P3B')]));
        $this->assertSame('v2', $token->keyVersion());
        $this->assertSame(['secret://migration/active'], $calls);
    }

    #[Test]
    public function malicious_noop_integrity_interface_cannot_construct_a_remediation_candidate(): void
    {
        $at = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $forged = new class implements VerifiedIntegrityAuthority
        {
            public function assertVerified(): void {}
        };

        $this->expectException(\TypeError::class);
        new RemediationCandidate(
            'R-UNVERIFIED', 'TOKEN-1', 'patient.dob', str_repeat('a', 64), 1,
            true, false, false, null, $at->modify('-1 day'), null,
            4, 5, 6, 'OWNER-DIRECTIVE', $forged,
        );
    }

    #[Test]
    public function retention_purge_is_blocked_without_owner_policy_or_while_held(): void
    {
        $now = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $policy = new RetentionPolicy('PENDING-OWNER-SCHEDULE', 'protected', 'migration-lineage', 365, true, false, $now, false, null, false, true);

        $this->expectExceptionMessage('LM-SEC-STORE-PURGE-POLICY-BLOCKED-001');
        (new RetentionPurgeGate)->assertEligible($policy, 'protected', 'migration-lineage', $now->modify('-2 years'), $now, true, false);
    }

    #[Test]
    public function approved_purge_still_rejects_lineage_destruction(): void
    {
        $now = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $policy = new RetentionPolicy('OWNER-POLICY-1', 'protected', 'migration-lineage', 30, false, false, $now->modify('-1 day'), true, 'OWNER-PURGE-AUTHORITY', true, true);

        $this->expectExceptionMessage('LM-SEC-STORE-PURGE-INTEGRITY-001');
        (new RetentionPurgeGate)->assertEligible($policy, 'protected', 'migration-lineage', $now->modify('-1 year'), $now, true, true);
    }

    /** @return array{ProtectedRecordEnvelopeFactory, ProtectedRecordEnvelopeVerifier, string} */
    private function envelopeServices(): array
    {
        $versions = new CanonicalizationVersionRegistry;
        $encoder = new CanonicalTypedMessageEncoder($versions);
        $tokens = new HmacTokenService(
            new ConfiguredKeyProvider([
                'key_id' => 'migration-hmac',
                'key_version' => 'v1',
                'key' => '56789abcdef01234FABCDE!@#$%^&*()-+=01234',
                'algorithm' => 'sha256',
            ]),
            $versions,
            'testing',
            new TokenDomainRegistry(['patient_source', 'artifact_integrity']),
        );
        $guard = new ProtectedStoreAccessGuard(
            new PinnedProtectedStoreAccessAuthority(
                'testing',
                ['patient_source'],
                'migration-hmac',
                'v1',
                'typed-length-prefix/1',
                operationPolicies: [
                    'synthetic_security_test' => [
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
        $token = $tokens->tokenize(new TokenDomain('patient_source'), $encoder->encode([TypedValue::string('SYNTHETIC-ONLY-P3B::ROW')]))->encode();

        return [new ProtectedRecordEnvelopeFactory($guard), new ProtectedRecordEnvelopeVerifier($guard), $token];
    }

    /** @param list<string> $operations */
    private function context(array $operations, int $runId = 4): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext('synthetic_security_test', $operations, ['patient_source'], 'testing', $runId, 5, 6, 'protected', 'migration-lineage', 'SYNTHETIC-AUTHORITY', ['bundle_version']);
    }

    /** @return array<string, array{encoded_token:string, domain:string}> */
    private function tokenSet(string $token): array
    {
        return ['identity_token' => ['encoded_token' => $token, 'domain' => 'patient_source']];
    }
}
