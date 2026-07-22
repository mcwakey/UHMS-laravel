<?php

namespace App\Services\LegacyMigration\Foundation\Security;

interface DomainAwareKeyProvider extends KeyProvider
{
    public function activeForDomain(string $domain): HmacKeyMaterial;

    public function getForDomain(string $domain, string $keyId, string $version): HmacKeyMaterial;
}
