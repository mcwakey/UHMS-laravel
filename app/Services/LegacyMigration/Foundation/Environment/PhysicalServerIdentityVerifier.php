<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class PhysicalServerIdentityVerifier
{
    public function __construct(private IdentityReferenceHasher $hasher) {}

    public function verify(
        PhysicalTargetIdentityContract $approved,
        PhysicalServerIdentityObserver $observer,
        SchemaObservation $structural,
        string $foundationSchemaCoordinate,
        string $configurationFingerprint,
    ): PhysicalServerIdentityVerification {
        $observed = $observer->observe();

        $this->same($approved->connection, $observed->connection);
        $this->same($approved->database, $observed->database);
        $this->same($approved->driver, $observed->driver);
        $this->same($approved->databaseVersion, $observed->databaseVersion);
        $this->same($approved->hostIdentityReference, $observed->hostIdentityReference);
        $this->same((string) $approved->port, (string) $observed->port);
        $this->same($approved->serverIdentityReference, $observed->serverIdentityReference);
        $this->same($approved->serverRoleClassification, $observed->serverRoleClassification);
        $this->same($approved->networkEnvironmentIdentityReference, $observed->networkEnvironmentIdentityReference);
        $this->same($approved->environmentAttestationVersion, $observed->environmentAttestationVersion);
        if ($approved->tlsRequired && ! $observed->tlsActive) {
            $this->mismatch();
        }
        if ($approved->tlsCipherReference !== null) {
            $this->same($approved->tlsCipherReference, (string) $observed->tlsCipherReference);
        }
        if ($approved->tlsPeerIdentityRequired) {
            $this->same((string) $approved->tlsPeerIdentityReference, (string) $observed->tlsPeerIdentityReference);
        }

        $this->same($approved->connection, $structural->connection);
        $this->same($approved->database, $structural->database);
        $this->same($approved->databaseVersion, $structural->databaseVersion);
        $this->same($approved->structuralSchemaFingerprint, $structural->fingerprint);
        $this->same((string) $approved->tableCount, (string) $structural->tableCount);
        $this->same((string) $approved->columnCount, (string) $structural->columnCount);
        $this->same($approved->foundationSchemaCoordinate, $foundationSchemaCoordinate);
        $this->same($approved->configurationFingerprint, $configurationFingerprint);

        $approvedIdentityReference = $this->hasher->reference('approved_identity_record', implode('|', [
            $approved->contractVersion,
            $approved->hostIdentityReference,
            (string) $approved->port,
            $approved->serverIdentityReference,
            $approved->networkEnvironmentIdentityReference,
            $approved->ownerApprovalReference,
        ]));

        return PhysicalServerIdentityVerification::issue(
            $approved->contractVersion,
            $approvedIdentityReference,
            $structural->fingerprint,
            $configurationFingerprint,
            $this->hasher,
            $observed->connectionInstanceReference === ''
                ? $this->hasher->reference('synthetic_connection_instance', $approvedIdentityReference)
                : $observed->connectionInstanceReference,
            $this->observationReference($observed),
        );
    }

    public function observationReference(ObservedPhysicalServerIdentity $observed): string
    {
        return $this->hasher->reference('observed_physical_target', json_encode([
            'connection' => $observed->connection,
            'database' => $observed->database,
            'driver' => $observed->driver,
            'database_version' => $observed->databaseVersion,
            'host' => $observed->hostIdentityReference,
            'port' => $observed->port,
            'tls_active' => $observed->tlsActive,
            'tls_cipher' => $observed->tlsCipherReference,
            'tls_peer' => $observed->tlsPeerIdentityReference,
            'server' => $observed->serverIdentityReference,
            'role' => $observed->serverRoleClassification,
            'network' => $observed->networkEnvironmentIdentityReference,
            'attestation' => $observed->environmentAttestationVersion,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function same(string $expected, string $observed): void
    {
        if ($expected === '' || $observed === '' || ! hash_equals($expected, $observed)) {
            $this->mismatch();
        }
    }

    private function mismatch(): never
    {
        throw new FoundationGuardException('FOUNDATION_PHYSICAL_TARGET_IDENTITY_MISMATCH', 'The active target does not match the approved non-production physical identity.');
    }
}
