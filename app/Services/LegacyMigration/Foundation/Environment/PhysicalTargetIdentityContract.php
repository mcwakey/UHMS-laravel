<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class PhysicalTargetIdentityContract
{
    public function __construct(
        public string $contractVersion,
        public string $connection,
        public string $database,
        public string $driver,
        public string $databaseVersion,
        public string $hostIdentityReference,
        public int $port,
        public bool $tlsRequired,
        public ?string $tlsCipherReference,
        public bool $tlsPeerIdentityRequired,
        public ?string $tlsPeerIdentityReference,
        public string $serverIdentityReference,
        public string $serverRoleClassification,
        public string $networkEnvironmentIdentityReference,
        public string $environmentAttestationVersion,
        public string $structuralSchemaFingerprint,
        public int $tableCount,
        public int $columnCount,
        public string $foundationSchemaCoordinate,
        public string $configurationFingerprint,
        public string $ownerApprovalReference,
    ) {
        foreach ([
            $contractVersion, $connection, $database, $driver, $databaseVersion,
            $hostIdentityReference, $serverIdentityReference, $serverRoleClassification,
            $networkEnvironmentIdentityReference, $environmentAttestationVersion,
            $structuralSchemaFingerprint, $foundationSchemaCoordinate,
            $configurationFingerprint, $ownerApprovalReference,
        ] as $required) {
            if (trim($required) === '') {
                throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract is incomplete.');
            }
        }
        foreach ([$hostIdentityReference, $serverIdentityReference, $networkEnvironmentIdentityReference, $structuralSchemaFingerprint, $foundationSchemaCoordinate, $configurationFingerprint] as $digest) {
            if (preg_match('/\A[a-f0-9]{64}\z/', strtolower($digest)) !== 1) {
                throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract contains an invalid protected reference.');
            }
        }
        foreach ([$tlsCipherReference, $tlsPeerIdentityReference] as $optionalDigest) {
            if ($optionalDigest !== null && preg_match('/\A[a-f0-9]{64}\z/', strtolower($optionalDigest)) !== 1) {
                throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract contains an invalid TLS identity reference.');
            }
        }
        if ($port < 1 || $port > 65535 || $tableCount < 1 || $columnCount < 1) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract contains an invalid coordinate.');
        }
        if ($tlsPeerIdentityRequired && ($tlsPeerIdentityReference === null || $tlsPeerIdentityReference === '')) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The required TLS peer identity is not pinned.');
        }
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        foreach (['tls_required', 'tls_peer_identity_required'] as $boolean) {
            if (! array_key_exists($boolean, $values) || ! is_bool($values[$boolean])) {
                throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract is incomplete.');
            }
        }

        return new self(
            contractVersion: self::string($values, 'contract_version'),
            connection: self::string($values, 'connection'),
            database: self::string($values, 'database'),
            driver: self::string($values, 'driver'),
            databaseVersion: self::string($values, 'database_version'),
            hostIdentityReference: self::string($values, 'host_identity_reference'),
            port: self::integer($values, 'port'),
            tlsRequired: $values['tls_required'],
            tlsCipherReference: self::nullableString($values, 'tls_cipher_reference'),
            tlsPeerIdentityRequired: $values['tls_peer_identity_required'],
            tlsPeerIdentityReference: self::nullableString($values, 'tls_peer_identity_reference'),
            serverIdentityReference: self::string($values, 'server_identity_reference'),
            serverRoleClassification: self::string($values, 'server_role_classification'),
            networkEnvironmentIdentityReference: self::string($values, 'network_environment_identity_reference'),
            environmentAttestationVersion: self::string($values, 'environment_attestation_version'),
            structuralSchemaFingerprint: self::string($values, 'structural_schema_fingerprint'),
            tableCount: self::integer($values, 'table_count'),
            columnCount: self::integer($values, 'column_count'),
            foundationSchemaCoordinate: self::string($values, 'foundation_schema_coordinate'),
            configurationFingerprint: self::string($values, 'configuration_fingerprint'),
            ownerApprovalReference: self::string($values, 'owner_approval_reference'),
        );
    }

    /** @param array<string, mixed> $values */
    private static function string(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract is incomplete.');
        }

        return trim($value);
    }

    /** @param array<string, mixed> $values */
    private static function nullableString(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;
        if ($value === null) {
            return null;
        }

        return self::string($values, $key);
    }

    /** @param array<string, mixed> $values */
    private static function integer(array $values, string $key): int
    {
        $value = $values[$key] ?? null;
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }
        if (! is_int($value)) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_CONTRACT_INVALID', 'The approved target identity contract is incomplete.');
        }

        return $value;
    }
}
