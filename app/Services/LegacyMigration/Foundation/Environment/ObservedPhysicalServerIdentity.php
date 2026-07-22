<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final readonly class ObservedPhysicalServerIdentity
{
    public function __construct(
        public string $connection,
        public string $database,
        public string $driver,
        public string $databaseVersion,
        public string $hostIdentityReference,
        public int $port,
        public bool $tlsActive,
        public ?string $tlsCipherReference,
        public ?string $tlsPeerIdentityReference,
        public string $serverIdentityReference,
        public string $serverRoleClassification,
        public string $networkEnvironmentIdentityReference,
        public string $environmentAttestationVersion,
        public string $connectionInstanceReference = '',
    ) {}

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['identity' => '[REDACTED_PHYSICAL_TARGET_IDENTITY]'];
    }
}
