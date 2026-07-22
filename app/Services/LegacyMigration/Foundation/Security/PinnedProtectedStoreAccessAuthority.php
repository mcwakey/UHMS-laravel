<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class PinnedProtectedStoreAccessAuthority implements ProtectedStoreAccessAuthority
{
    /** @var list<string> */
    private array $permittedDomains;

    /** @param list<string> $permittedDomains */
    public function __construct(
        private readonly string $environment,
        array $permittedDomains,
        private readonly string $keyId,
        private readonly string $keyVersion,
        private readonly string $canonicalizationVersion,
        private readonly string $integrityDomain = 'artifact_integrity',
    ) {
        if ($this->environment === '' || $this->keyId === '' || $this->keyVersion === '' || $this->canonicalizationVersion === '') {
            throw SecurityConfigurationException::forCode('LM-SEC-STORE-AUTHORITY-001');
        }

        $domains = [];
        foreach ($permittedDomains as $domain) {
            $validated = new TokenDomain($domain);
            $domains[$validated->value()] = true;
        }
        if ($domains === [] || isset($domains[$this->integrityDomain])) {
            throw SecurityConfigurationException::forCode('LM-SEC-STORE-AUTHORITY-002');
        }
        new TokenDomain($this->integrityDomain);
        $this->permittedDomains = array_keys($domains);
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function permittedDomains(): array
    {
        return $this->permittedDomains;
    }

    public function keyId(): string
    {
        return $this->keyId;
    }

    public function keyVersion(): string
    {
        return $this->keyVersion;
    }

    public function canonicalizationVersion(): string
    {
        return $this->canonicalizationVersion;
    }

    public function integrityDomain(): string
    {
        return $this->integrityDomain;
    }
}
