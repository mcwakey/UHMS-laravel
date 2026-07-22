<?php

namespace Tests\Unit\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use App\Services\LegacyMigration\Foundation\Snapshot\CoordinatedSourceSnapshotManager;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotException;
use App\Services\LegacyMigration\Foundation\Snapshot\TargetCollisionSnapshotManager;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SnapshotManagersTest extends TestCase
{
    private const RUN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const CONFIG = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private const CONTRACT = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    #[Test]
    public function source_snapshot_coordinates_patient_children_and_insurance_deterministically(): void
    {
        $manager = new CoordinatedSourceSnapshotManager;
        $first = $manager->create(
            self::RUN,
            $this->source(),
            self::CONFIG,
            self::CONTRACT,
            ['patient_query' => str_repeat('1', 64), 'insurance_query' => str_repeat('2', 64)],
            [
                'patient_root' => str_repeat('3', 64),
                'patient_children' => str_repeat('4', 64),
                'insurance' => str_repeat('5', 64),
            ],
            new DateTimeImmutable('2026-07-22T10:00:00+02:00'),
        );
        $second = $manager->create(
            self::RUN,
            $this->source(),
            self::CONFIG,
            self::CONTRACT,
            ['insurance_query' => str_repeat('2', 64), 'patient_query' => str_repeat('1', 64)],
            [
                'insurance' => str_repeat('5', 64),
                'patient_children' => str_repeat('4', 64),
                'patient_root' => str_repeat('3', 64),
            ],
            new DateTimeImmutable('2026-07-23T00:00:00Z'),
        );

        $this->assertSame($first->snapshotId, $second->snapshotId);
        $this->assertSame('2026-07-22T08:00:00+00:00', $first->capturedAtUtc);
        $this->assertFalse($first->toArray()['contains_raw_identifiers']);
        $this->assertSame(0, $first->toArray()['database_writes']);
    }

    #[Test]
    public function incomplete_source_coordinate_fails_closed(): void
    {
        $this->expectException(SnapshotException::class);
        $this->expectExceptionMessage('incomplete');
        (new CoordinatedSourceSnapshotManager)->create(
            self::RUN,
            $this->source(),
            self::CONFIG,
            self::CONTRACT,
            ['patient_query' => str_repeat('1', 64)],
            ['patient_root' => str_repeat('3', 64), 'patient_children' => str_repeat('4', 64)],
            new DateTimeImmutable('2026-07-22T00:00:00Z'),
        );
    }

    #[Test]
    public function source_drift_invalidates_all_descendants(): void
    {
        $manager = new CoordinatedSourceSnapshotManager;
        $pinned = $this->sourceSnapshot($manager, str_repeat('5', 64));
        $current = $this->sourceSnapshot($manager, str_repeat('6', 64));

        try {
            $manager->assertUnchanged($pinned, $current);
            $this->fail('Expected snapshot drift rejection.');
        } catch (SnapshotException $exception) {
            $this->assertSame('FOUNDATION_SOURCE_SNAPSHOT_DRIFT', $exception->faultCode);
            $this->assertStringContainsString('all descendants', $exception->getMessage());
        }
    }

    #[Test]
    public function target_snapshot_pins_row_schema_and_configuration_collision_sets(): void
    {
        $manager = new TargetCollisionSnapshotManager;
        $snapshot = $manager->create(
            self::RUN,
            $this->target(),
            self::CONFIG,
            self::CONTRACT,
            ['collision_query_v1' => str_repeat('1', 64)],
            $this->targetSets(),
            new DateTimeImmutable('2026-07-22T00:00:00Z'),
        );

        $this->assertSame('target_collision', $snapshot->kind);
        $this->assertSame(array_keys($this->targetSets()), array_keys($snapshot->setHashes));
        $manager->assertCurrent($snapshot, $snapshot);
    }

    #[Test]
    public function target_row_or_configuration_drift_requires_recapture(): void
    {
        $manager = new TargetCollisionSnapshotManager;
        $pinned = $manager->create(self::RUN, $this->target(), self::CONFIG, self::CONTRACT, ['q' => str_repeat('1', 64)], $this->targetSets(), new DateTimeImmutable);
        $changedSets = $this->targetSets();
        $changedSets['number_configuration'] = str_repeat('f', 64);
        $current = $manager->create(self::RUN, $this->target(), self::CONFIG, self::CONTRACT, ['q' => str_repeat('1', 64)], $changedSets, new DateTimeImmutable);

        try {
            $manager->assertCurrent($pinned, $current);
            $this->fail('Expected target snapshot drift rejection.');
        } catch (SnapshotException $exception) {
            $this->assertSame('FOUNDATION_TARGET_SNAPSHOT_DRIFT', $exception->faultCode);
        }
    }

    private function source(): SchemaObservation
    {
        return new SchemaObservation('legacy_uhms', 'uuhms', '10.4.32-MariaDB', GuardConfiguration::SOURCE_FINGERPRINT, 55, 479);
    }

    private function target(): SchemaObservation
    {
        return new SchemaObservation('mysql', 'uhms_clean', '10.4.32-MariaDB', str_repeat('9', 64), 335, 5347);
    }

    private function sourceSnapshot(CoordinatedSourceSnapshotManager $manager, string $insuranceHash): \App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest
    {
        return $manager->create(
            self::RUN,
            $this->source(),
            self::CONFIG,
            self::CONTRACT,
            ['q' => str_repeat('1', 64)],
            ['patient_root' => str_repeat('3', 64), 'patient_children' => str_repeat('4', 64), 'insurance' => $insuranceHash],
            new DateTimeImmutable('2026-07-22T00:00:00Z'),
        );
    }

    /** @return array<string, string> */
    private function targetSets(): array
    {
        return [
            'alias_namespace' => str_repeat('1', 64),
            'contact_sets' => str_repeat('2', 64),
            'insurance_memberships' => str_repeat('3', 64),
            'number_configuration' => str_repeat('4', 64),
            'patient_namespace' => str_repeat('5', 64),
            'row_schema' => str_repeat('6', 64),
        ];
    }
}
