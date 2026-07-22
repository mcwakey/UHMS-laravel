<?php

namespace App\Services\LegacyMigration\Foundation\Security;

interface ProtectedStoreAccessAuthority
{
    public function environment(): string;

    /** @return list<string> */
    public function permittedDomains(): array;

    public function keyId(): string;

    public function keyVersion(): string;

    public function permitsKeyContext(string $keyId, string $keyVersion): bool;

    public function canonicalizationVersion(): string;

    public function integrityDomain(): string;

    public function assertOperationContext(ProtectedStoreOperationContext $context, string $operation, string $domain): void;
}
