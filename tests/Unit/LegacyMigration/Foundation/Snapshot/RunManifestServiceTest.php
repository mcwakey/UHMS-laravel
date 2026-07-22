<?php

namespace Tests\Unit\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Snapshot\CoordinatedSourceSnapshotManager;
use App\Services\LegacyMigration\Foundation\Snapshot\RunManifestService;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotException;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifestIntegrityService;
use App\Services\LegacyMigration\Foundation\Snapshot\TargetCollisionSnapshotManager;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedCohortAuthority;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedEnvironmentAuthority;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedHmacAuthority;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedRemediationAuthority;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedRunPrerequisites;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedTargetStatePolicyAuthority;
use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\LegacyMigration\Phase2FPolicyFixture;

final class RunManifestServiceTest extends TestCase
{
    private const RUN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const CONFIG = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    #[Test]
    public function manifest_pins_bounded_cohort_snapshots_and_complete_version_bundle(): void
    {
        [$source, $target] = $this->snapshots();
        $bundle = Phase2FPolicyFixture::load();
        $manifest = $this->service()->create(
            $source,
            $target,
            $bundle,
            self::CONFIG,
            $this->prerequisites($source, $target, $bundle),
            [
                'canonicalization' => 'typed-length-prefix/1.0.0',
                'phase2f' => '2F.1.0',
                'snapshot' => '3.0.0',
                'transformation' => 'not-authorized',
            ],
        );
        $array = $manifest->toArray();

        $this->assertSame($source->snapshotId, $array['source_snapshot_id']);
        $this->assertSame($target->snapshotId, $array['target_snapshot_id']);
        $this->assertFalse($array['contains_raw_identifiers']);
        $this->assertStringNotContainsString('PAT_ID', json_encode($array, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function mismatched_configuration_coordinate_fails_closed(): void
    {
        [$source, $target] = $this->snapshots();
        $bundle = Phase2FPolicyFixture::load();

        try {
            $this->service()->create(
                $source, $target, $bundle, str_repeat('e', 64), $this->prerequisites($source, $target, $bundle), ['phase2f' => '2F.1.0'],
            );
            $this->fail('Expected coordinate mismatch.');
        } catch (SnapshotException $exception) {
            $this->assertSame('FOUNDATION_RUN_COORDINATE_MISMATCH', $exception->faultCode);
        }
    }

    /** @return array{0: SnapshotManifest, 1: SnapshotManifest} */
    private function snapshots(): array
    {
        $capturedAt = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $bundle = Phase2FPolicyFixture::load();
        $source = $this->authoritative((new CoordinatedSourceSnapshotManager)->create(
            self::RUN,
            new SchemaObservation('legacy_uhms', 'uuhms', '10.4.32-MariaDB', GuardConfiguration::SOURCE_FINGERPRINT, 55, 479),
            self::CONFIG,
            $bundle->bundleHash,
            ['q' => str_repeat('1', 64)],
            ['patient_root' => str_repeat('2', 64), 'patient_children' => str_repeat('3', 64), 'insurance' => str_repeat('4', 64)],
            $capturedAt,
        ));
        $target = $this->authoritative((new TargetCollisionSnapshotManager)->create(
            self::RUN,
            new SchemaObservation('mysql', 'uhms_clean', '10.4.32-MariaDB', str_repeat('9', 64), 335, 5347),
            self::CONFIG,
            $bundle->bundleHash,
            ['q' => str_repeat('1', 64)],
            [
                'patient_namespace' => str_repeat('1', 64),
                'alias_namespace' => str_repeat('2', 64),
                'contact_sets' => str_repeat('3', 64),
                'insurance_memberships' => str_repeat('4', 64),
                'row_schema' => str_repeat('5', 64),
                'number_configuration' => str_repeat('6', 64),
            ],
            $capturedAt,
        ));

        return [$source, $target];
    }

    #[Test]
    public function caller_supplied_hash_snapshots_cannot_create_an_authoritative_run(): void
    {
        $bundle = Phase2FPolicyFixture::load();
        $snapshot = (new CoordinatedSourceSnapshotManager)->create(
            self::RUN,
            new SchemaObservation('legacy_uhms', 'uuhms', '10.4.32-MariaDB', GuardConfiguration::SOURCE_FINGERPRINT, 55, 479),
            self::CONFIG,
            $bundle->bundleHash,
            ['q' => str_repeat('1', 64)],
            ['patient_root' => str_repeat('2', 64), 'patient_children' => str_repeat('3', 64), 'insurance' => str_repeat('4', 64)],
            new DateTimeImmutable,
        );
        [$authoritySource, $target] = $this->snapshots();

        try {
            $this->service()->create($snapshot, $target, $bundle, self::CONFIG, $this->prerequisites($authoritySource, $target, $bundle), ['phase2f' => '2F.1.0']);
            self::fail('Caller-asserted snapshot must be rejected.');
        } catch (SnapshotException $exception) {
            self::assertSame('FOUNDATION_RUN_CALLER_ASSERTED_EVIDENCE_REJECTED', $exception->faultCode);
        }
    }

    #[Test]
    public function altered_authoritative_snapshot_metadata_cannot_replay_its_integrity_seal(): void
    {
        $bundle = Phase2FPolicyFixture::load();
        [$source, $target] = $this->snapshots();
        $tampered = new SnapshotManifest(
            $source->snapshotId, $source->kind, $source->runToken, $source->capturedAtUtc, $source->schemaFingerprint,
            $source->databaseVersion, $source->configurationFingerprint, $source->contractBundleHash, $source->queryHashes,
            $source->setHashes, $source->authority, str_repeat('f', 64), $source->protectedSetTokens, $source->authoritySeal,
        );

        try {
            $this->service()->create($tampered, $target, $bundle, self::CONFIG, $this->prerequisites($source, $target, $bundle), ['phase2f' => '2F.1.0']);
            self::fail('Altered authoritative snapshot metadata must fail.');
        } catch (SnapshotException $exception) {
            self::assertSame('FOUNDATION_RUN_CALLER_ASSERTED_EVIDENCE_REJECTED', $exception->faultCode);
        }
    }

    private function authoritative(SnapshotManifest $snapshot): SnapshotManifest
    {
        return $this->integrity()->seal(new SnapshotManifest(
            $snapshot->snapshotId,
            $snapshot->kind,
            $snapshot->runToken,
            $snapshot->capturedAtUtc,
            $snapshot->schemaFingerprint,
            $snapshot->databaseVersion,
            $snapshot->configurationFingerprint,
            $snapshot->contractBundleHash,
            $snapshot->queryHashes,
            [],
            'authoritative_direct_capture',
            str_repeat('a', 64),
            ['synthetic' => $this->artifactToken('synthetic')],
        ));
    }

    private function prerequisites(SnapshotManifest $source, SnapshotManifest $target, VerifiedPolicyBundle $bundle): VerifiedRunPrerequisites
    {
        return VerifiedRunPrerequisites::fromAuthorities(
            VerifiedCohortAuthority::fromPolicyBundle($bundle),
            VerifiedHmacAuthority::fromSnapshots($source, $target, $this->integrity()),
            VerifiedEnvironmentAuthority::fromSnapshots($source, $target, $this->integrity()),
            VerifiedRemediationAuthority::fromPolicyBundle($bundle),
            VerifiedTargetStatePolicyAuthority::fromPolicyBundle($bundle),
        );
    }

    private function artifactToken(string $value): string
    {
        $versions = new CanonicalizationVersionRegistry;
        $hmac = new HmacTokenService(new ConfiguredKeyProvider([
            'key_id' => 'synthetic-p3b', 'key_version' => 'v1',
            'key' => '789abcdef0123456BCDEFA!@#$%^&*()-+=0123456', 'algorithm' => 'sha256',
        ]), $versions, 'testing');

        return $hmac->tokenize(
            new TokenDomain('artifact_integrity'),
            (new CanonicalTypedMessageEncoder($versions))->encode([TypedValue::string($value)]),
        )->encode();
    }

    private function service(): RunManifestService
    {
        return new RunManifestService($this->integrity());
    }

    private function integrity(): SnapshotManifestIntegrityService
    {
        $versions = new CanonicalizationVersionRegistry;
        $hmac = new HmacTokenService(new ConfiguredKeyProvider([
            'key_id' => 'synthetic-p3b', 'key_version' => 'v1',
            'key' => '789abcdef0123456BCDEFA!@#$%^&*()-+=0123456', 'algorithm' => 'sha256',
        ]), $versions, 'testing');

        return new SnapshotManifestIntegrityService($hmac, new CanonicalTypedMessageEncoder($versions));
    }
}
