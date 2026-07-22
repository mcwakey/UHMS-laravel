<?php

namespace Tests\Unit\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use App\Services\LegacyMigration\Foundation\Snapshot\CoordinatedSourceSnapshotManager;
use App\Services\LegacyMigration\Foundation\Snapshot\RunManifestService;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotException;
use App\Services\LegacyMigration\Foundation\Snapshot\TargetCollisionSnapshotManager;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RunManifestServiceTest extends TestCase
{
    private const RUN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const CONFIG = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private const CONTRACT = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    #[Test]
    public function manifest_pins_bounded_cohort_snapshots_and_complete_version_bundle(): void
    {
        [$source, $target] = $this->snapshots();
        $manifest = (new RunManifestService)->create(
            $source,
            $target,
            str_repeat('d', 64),
            self::CONTRACT,
            self::CONFIG,
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

        try {
            (new RunManifestService)->create(
                $source, $target, str_repeat('d', 64), self::CONTRACT, str_repeat('e', 64), ['phase2f' => '2F.1.0'],
            );
            $this->fail('Expected coordinate mismatch.');
        } catch (SnapshotException $exception) {
            $this->assertSame('FOUNDATION_RUN_COORDINATE_MISMATCH', $exception->faultCode);
        }
    }

    /** @return array{0: \App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest, 1: \App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest} */
    private function snapshots(): array
    {
        $capturedAt = new DateTimeImmutable('2026-07-22T00:00:00Z');
        $source = (new CoordinatedSourceSnapshotManager)->create(
            self::RUN,
            new SchemaObservation('legacy_uhms', 'uuhms', '10.4.32-MariaDB', GuardConfiguration::SOURCE_FINGERPRINT, 55, 479),
            self::CONFIG,
            self::CONTRACT,
            ['q' => str_repeat('1', 64)],
            ['patient_root' => str_repeat('2', 64), 'patient_children' => str_repeat('3', 64), 'insurance' => str_repeat('4', 64)],
            $capturedAt,
        );
        $target = (new TargetCollisionSnapshotManager)->create(
            self::RUN,
            new SchemaObservation('mysql', 'uhms_clean', '10.4.32-MariaDB', str_repeat('9', 64), 335, 5347),
            self::CONFIG,
            self::CONTRACT,
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
        );

        return [$source, $target];
    }
}
