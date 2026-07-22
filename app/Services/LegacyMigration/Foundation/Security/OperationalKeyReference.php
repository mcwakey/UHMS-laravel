<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use DateTimeImmutable;

final class OperationalKeyReference
{
    /** @param list<string> $domains */
    public function __construct(
        public readonly string $keyId,
        public readonly string $version,
        public readonly string $secretReference,
        public readonly string $environment,
        public readonly array $domains,
        public readonly DateTimeImmutable $activatedAt,
        public readonly ?DateTimeImmutable $retiredAt,
        public readonly ?DateTimeImmutable $verificationExpiresAt,
        public readonly string $rotationAuthority,
        public readonly bool $revoked,
        public readonly bool $activeForSigning,
    ) {
        if ($this->keyId === '' || $this->version === '' || $this->secretReference === '' || $this->environment === '' || $this->rotationAuthority === '' || $this->domains === []) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-REFERENCE-METADATA-001');
        }
        if ($this->activeForSigning && ($this->revoked || $this->retiredAt !== null)) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-ACTIVE-STATE-001');
        }
        if ($this->retiredAt !== null && $this->verificationExpiresAt !== null && $this->verificationExpiresAt < $this->retiredAt) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-LIFECYCLE-001');
        }
        foreach ($this->domains as $domain) {
            new TokenDomain($domain);
        }
    }

    public function assertUsable(string $environment, string $domain, DateTimeImmutable $at, bool $forSigning): void
    {
        if (! hash_equals($this->environment, $environment) || ! in_array($domain, $this->domains, true) || $this->revoked || $at < $this->activatedAt) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-SCOPE-001');
        }
        if ($forSigning && (! $this->activeForSigning || $this->retiredAt !== null)) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-SIGNING-001');
        }
        if (! $forSigning && $this->verificationExpiresAt !== null && $at > $this->verificationExpiresAt) {
            throw SecurityConfigurationException::forCode('LM-SEC-KEY-VERIFICATION-EXPIRED-001');
        }
    }
}
