<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class TokenDomainRegistry
{
    /** @var array<string, true> */
    private array $domains = [];

    /** @param list<string> $domains */
    public function __construct(array $domains)
    {
        if ($domains === []) {
            throw SecurityConfigurationException::forCode('LM-SEC-DOMAIN-REGISTRY-001');
        }

        foreach ($domains as $domain) {
            $validated = new TokenDomain($domain);
            if (isset($this->domains[$validated->value()])) {
                throw SecurityConfigurationException::forCode('LM-SEC-DOMAIN-REGISTRY-002');
            }
            $this->domains[$validated->value()] = true;
        }
    }

    public function get(string $domain): TokenDomain
    {
        if (! isset($this->domains[$domain])) {
            throw SecurityConfigurationException::forCode('LM-SEC-DOMAIN-NOT-ALLOWED-001');
        }

        return new TokenDomain($domain);
    }

    public function assertAllowed(TokenDomain $domain): void
    {
        if (! isset($this->domains[$domain->value()])) {
            throw SecurityConfigurationException::forCode('LM-SEC-DOMAIN-NOT-ALLOWED-001');
        }
    }
}
