<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use Closure;
use DateTimeImmutable;

/** Resolves external secret references at use time; key bytes never enter config cache. */
final class ExternalReferenceKeyProvider implements DomainAwareKeyProvider
{
    /** @var array<string, OperationalKeyReference> */
    private array $references = [];

    /** @var Closure(string): (string|null) */
    private Closure $resolver;

    /**
     * @param  list<OperationalKeyReference>  $references
     * @param  callable(string): (string|null)  $secretResolver
     */
    public function __construct(
        array $references,
        private readonly string $environment,
        private readonly string $domain,
        callable $secretResolver,
        private readonly ?DateTimeImmutable $clock = null,
    ) {
        $this->resolver = Closure::fromCallable($secretResolver);
        foreach ($references as $reference) {
            if (! $reference instanceof OperationalKeyReference) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-LIST-001');
            }
            $key = $reference->keyId."\0".$reference->version;
            if (isset($this->references[$key])) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-CONFIG-004');
            }
            $this->references[$key] = $reference;
        }
        $activeByDomain = [];
        foreach ($this->references as $reference) {
            if (! $reference->activeForSigning) {
                continue;
            }
            foreach ($reference->domains as $domain) {
                $activeByDomain[$domain] = ($activeByDomain[$domain] ?? 0) + 1;
            }
        }
        if (($activeByDomain[$this->domain] ?? 0) !== 1
            || array_filter($activeByDomain, static fn (int $count): bool => $count !== 1) !== []) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-ACTIVE-COUNT-001');
        }
    }

    public function active(): HmacKeyMaterial
    {
        return $this->activeForDomain($this->domain);
    }

    public function activeForDomain(string $domain): HmacKeyMaterial
    {
        $matched = [];
        foreach ($this->references as $reference) {
            if ($reference->activeForSigning && in_array($domain, $reference->domains, true)) {
                $matched[] = $reference;
            }
        }
        if (count($matched) !== 1) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-ACTIVE-COUNT-001');
        }

        return $this->resolve($matched[0], true, $domain);
    }

    public function get(string $keyId, string $version): HmacKeyMaterial
    {
        return $this->getForDomain($this->domain, $keyId, $version);
    }

    public function getForDomain(string $domain, string $keyId, string $version): HmacKeyMaterial
    {
        $reference = $this->references[$keyId."\0".$version] ?? null;
        if ($reference === null) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-NOT-AVAILABLE-001');
        }

        return $this->resolve($reference, false, $domain);
    }

    private function resolve(OperationalKeyReference $reference, bool $forSigning, string $domain): HmacKeyMaterial
    {
        $reference->assertUsable($this->environment, $domain, $this->clock ?? new DateTimeImmutable('now'), $forSigning);
        $secret = ($this->resolver)($reference->secretReference);
        if (! is_string($secret) || $secret === '') {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-MISSING-001');
        }
        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded === false) {
                throw SecurityConfigurationException::forCode('LM-SEC-KEY-MATERIAL-ENCODING-001');
            }
            $secret = $decoded;
        }

        return new HmacKeyMaterial($reference->keyId, $reference->version, $secret);
    }
}
