<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class CanonicalMessage
{
    public function __construct(
        private readonly string $version,
        private readonly string $encoded,
    ) {}

    public function version(): string
    {
        return $this->version;
    }

    public function authenticate(HmacKeyMaterial $key, string $domainContext): string
    {
        return $key->authenticate($domainContext, $this->encoded);
    }
}
