<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

use Illuminate\Database\ConnectionInterface;
use PDO;
use Throwable;

/**
 * Reads physical identity only from the active connection and a separately
 * administered environment-attestation table. No caller-supplied observation
 * is accepted.
 */
final readonly class LaravelPhysicalServerIdentityObserver implements PhysicalServerIdentityObserver
{
    public function __construct(
        private string $connectionName,
        private ConnectionInterface $connection,
        private IdentityReferenceHasher $hasher,
    ) {}

    public function observe(): ObservedPhysicalServerIdentity
    {
        if (! in_array($this->connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_UNSUPPORTED', 'Physical target identity requires an approved MariaDB/MySQL connection.');
        }

        try {
            $server = (array) $this->connection->selectOne(
                'SELECT DATABASE() AS database_name, VERSION() AS database_version, @@hostname AS server_hostname, @@port AS server_port, @@server_id AS server_id, CONNECTION_ID() AS connection_id'
            );
            $tlsRows = $this->connection->select(
                "SHOW SESSION STATUS WHERE Variable_name IN ('Ssl_cipher', 'Ssl_version')"
            );
            $attestation = (array) $this->connection->selectOne(
                'SELECT server_role, environment_identity, attestation_version, tls_peer_identity FROM migration_environment_attestations WHERE active = 1'
            );
            $connectionStatus = (string) $this->connection->getPdo()->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        } catch (Throwable) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_UNAVAILABLE', 'The active target physical identity could not be authoritatively observed.');
        }

        if (count($this->connection->select('SELECT 1 FROM migration_environment_attestations WHERE active = 1')) !== 1) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_ATTESTATION_INVALID', 'The approved physical target attestation is missing or ambiguous.');
        }

        $tls = [];
        foreach ($tlsRows as $row) {
            $values = (array) $row;
            $name = (string) ($values['Variable_name'] ?? $values['variable_name'] ?? '');
            $tls[$name] = (string) ($values['Value'] ?? $values['value'] ?? '');
        }
        $cipher = trim($tls['Ssl_cipher'] ?? '');
        $tlsPeer = trim((string) ($attestation['tls_peer_identity'] ?? ''));
        $host = $this->required($server, 'server_hostname');
        $port = filter_var($server['server_port'] ?? null, FILTER_VALIDATE_INT);
        if ($port === false || $port < 1 || $port > 65535) {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_UNAVAILABLE', 'The active target physical identity could not be authoritatively observed.');
        }

        return new ObservedPhysicalServerIdentity(
            connection: $this->connectionName,
            database: $this->required($server, 'database_name'),
            driver: $this->connection->getDriverName(),
            databaseVersion: $this->required($server, 'database_version'),
            hostIdentityReference: $this->hasher->reference('server_hostname', $host),
            port: $port,
            tlsActive: $cipher !== '',
            tlsCipherReference: $cipher === '' ? null : $this->hasher->reference('tls_cipher', $cipher),
            tlsPeerIdentityReference: $tlsPeer === '' ? null : $this->hasher->reference('tls_peer_identity', $tlsPeer),
            serverIdentityReference: $this->hasher->reference('server_equivalent_identity', implode('|', [
                $host,
                (string) $port,
                $this->required($server, 'server_id'),
                $this->required($server, 'database_version'),
            ])),
            serverRoleClassification: $this->required($attestation, 'server_role'),
            networkEnvironmentIdentityReference: $this->hasher->reference('network_environment_identity', implode('|', [
                $connectionStatus,
                $this->required($attestation, 'environment_identity'),
            ])),
            environmentAttestationVersion: $this->required($attestation, 'attestation_version'),
            connectionInstanceReference: $this->hasher->reference('database_connection_instance', implode('|', [
                $this->required($server, 'database_name'),
                $this->required($server, 'server_hostname'),
                $this->required($server, 'server_id'),
                $this->required($server, 'connection_id'),
                $connectionStatus,
            ])),
        );
    }

    /** @param array<string, mixed> $row */
    private function required(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (! is_scalar($value) || trim((string) $value) === '') {
            throw new FoundationGuardException('FOUNDATION_PHYSICAL_IDENTITY_UNAVAILABLE', 'The active target physical identity could not be authoritatively observed.');
        }

        return trim((string) $value);
    }
}
