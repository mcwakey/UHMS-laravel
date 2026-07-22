<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\IdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\MetadataConnection;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalTargetIdentityContract;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;

final readonly class PhysicalIdentityTargetSnapshotAuthority implements TargetSnapshotAuthority
{
    public function __construct(
        private PhysicalServerIdentityVerifier $verifier,
        private PhysicalTargetIdentityContract $approved,
        private PhysicalServerIdentityObserver $observer,
        private IdentityReferenceHasher $hasher,
        private string $foundationSchemaCoordinate,
    ) {}

    public function verify(MetadataConnection $connection, SchemaObservation $structural, string $configurationFingerprint): string
    {
        if ($connection->name() !== $structural->connection || $connection->configuredDatabase() !== $structural->database) {
            throw new SnapshotException('FOUNDATION_TARGET_IDENTITY_COORDINATE_INVALID', 'The target identity observation does not match the active capture connection.');
        }
        $verification = $this->verifier->verify(
            $this->approved,
            $this->observer,
            $structural,
            $this->foundationSchemaCoordinate,
            $configurationFingerprint,
        );
        if (! $verification->isAuthentic($this->hasher)) {
            throw new SnapshotException('FOUNDATION_TARGET_IDENTITY_AUTHENTICITY_INVALID', 'The target physical identity proof is invalid.');
        }

        return $verification->approvedIdentityReference;
    }
}
