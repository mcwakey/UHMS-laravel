<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class PinnedProtectedStoreAccessAuthority implements ProtectedStoreAccessAuthority
{
    /** @var list<string> */
    private array $permittedDomains;

    /** @var array<string, array{operations:array<string,true>,authority_references:array<string,true>,access_classifications:array<string,true>,retention_classifications:array<string,true>}> */
    private array $operationPolicies;

    /** @var array<string, true> */
    private array $verificationKeyReferences;

    /**
     * @param  list<string>  $permittedDomains
     * @param  array<string, array{operations:list<string>,authority_references:list<string>,access_classifications:list<string>,retention_classifications:list<string>}>  $operationPolicies
     * @param  list<array{key_id:string,version:string}>  $verificationKeyReferences
     */
    public function __construct(
        private readonly string $environment,
        array $permittedDomains,
        private readonly string $keyId,
        private readonly string $keyVersion,
        private readonly string $canonicalizationVersion,
        private readonly string $integrityDomain = 'artifact_integrity',
        array $operationPolicies = [],
        array $verificationKeyReferences = [],
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
        $this->verificationKeyReferences = [$this->keyReference($this->keyId, $this->keyVersion) => true];
        foreach ($verificationKeyReferences as $reference) {
            $keyId = $reference['key_id'] ?? null;
            $version = $reference['version'] ?? null;
            if (! is_string($keyId) || $keyId === '' || ! is_string($version) || $version === '') {
                throw SecurityConfigurationException::forCode('LM-SEC-STORE-AUTHORITY-KEY-CONTEXT-001');
            }
            $this->verificationKeyReferences[$this->keyReference($keyId, $version)] = true;
        }
        $this->operationPolicies = [];
        foreach ($operationPolicies as $purpose => $policy) {
            if (! is_string($purpose) || $purpose === '' || ! is_array($policy)) {
                throw SecurityConfigurationException::forCode('LM-SEC-STORE-AUTHORITY-POLICY-001');
            }
            $normalized = [];
            foreach (['operations', 'authority_references', 'access_classifications', 'retention_classifications'] as $field) {
                $values = $policy[$field] ?? null;
                if (! is_array($values) || $values === []) {
                    throw SecurityConfigurationException::forCode('LM-SEC-STORE-AUTHORITY-POLICY-001');
                }
                $normalized[$field] = [];
                foreach ($values as $value) {
                    if (! is_string($value) || $value === '') {
                        throw SecurityConfigurationException::forCode('LM-SEC-STORE-AUTHORITY-POLICY-001');
                    }
                    $normalized[$field][$value] = true;
                }
            }
            $this->operationPolicies[$purpose] = $normalized;
        }
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

    public function permitsKeyContext(string $keyId, string $keyVersion): bool
    {
        return isset($this->verificationKeyReferences[$this->keyReference($keyId, $keyVersion)]);
    }

    public function canonicalizationVersion(): string
    {
        return $this->canonicalizationVersion;
    }

    public function integrityDomain(): string
    {
        return $this->integrityDomain;
    }

    public function assertOperationContext(ProtectedStoreOperationContext $context, string $operation, string $domain): void
    {
        $policy = $this->operationPolicies[$context->purpose()] ?? null;
        if ($policy === null
            || ! isset($policy['operations'][$operation])
            || ! isset($policy['authority_references'][$context->authorityReference()])
            || ! isset($policy['access_classifications'][$context->accessClassification()])
            || ! isset($policy['retention_classifications'][$context->retentionClassification()])
            || ! hash_equals($this->environment, $context->environment())
            || ! in_array($domain, $this->permittedDomains, true)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-AUTHORITY-CONTEXT-001');
        }
    }

    private function keyReference(string $keyId, string $version): string
    {
        return $keyId."\0".$version;
    }
}
