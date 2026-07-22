<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class HmacTokenService
{
    public function __construct(
        private readonly KeyProvider $keys,
        private readonly CanonicalizationVersionRegistry $canonicalizationVersions,
        private readonly string $environment,
        private readonly ?TokenDomainRegistry $domains = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $this->environment) !== 1) {
            throw SecurityConfigurationException::forCode('LM-SEC-ENVIRONMENT-001');
        }
    }

    public function tokenize(TokenDomain $domain, CanonicalMessage $message): ProtectedToken
    {
        $this->domains?->assertAllowed($domain);
        $this->canonicalizationVersions->assertSupported($message->version());
        $key = $this->keys->active();

        return $this->tokenizeWithKey($domain, $message, $key);
    }

    public function verify(TokenDomain $domain, CanonicalMessage $message, ProtectedToken $token): bool
    {
        $this->domains?->assertAllowed($domain);
        $this->assertContext($domain, $message, $token);
        $key = $this->keys->get($token->keyId(), $token->keyVersion());
        $expected = $this->tokenizeWithKey($domain, $message, $key);

        return $token->matches($expected);
    }

    public function rotate(TokenDomain $domain, CanonicalMessage $message, ProtectedToken $token): TokenRotationResult
    {
        if (! $this->verify($domain, $message, $token)) {
            throw TokenContextMismatchException::create();
        }

        $active = $this->keys->active();
        $alreadyActive = hash_equals($active->keyId(), $token->keyId())
            && hash_equals($active->version(), $token->keyVersion());

        return new TokenRotationResult(
            $alreadyActive ? $token : $this->tokenizeWithKey($domain, $message, $active),
            $token->keyId(),
            $token->keyVersion(),
            ! $alreadyActive,
        );
    }

    private function tokenizeWithKey(TokenDomain $domain, CanonicalMessage $message, HmacKeyMaterial $key): ProtectedToken
    {
        $context = $this->context($domain, $message, $key);
        $digest = $message->authenticate($key, $context);

        return new ProtectedToken(
            $this->environment,
            $domain->value(),
            $key->keyId(),
            $key->version(),
            $message->version(),
            $digest,
        );
    }

    private function assertContext(TokenDomain $domain, CanonicalMessage $message, ProtectedToken $token): void
    {
        $this->canonicalizationVersions->assertSupported($message->version());
        if (! hash_equals($this->environment, $token->environment())
            || ! hash_equals($domain->value(), $token->domain())
            || ! hash_equals($message->version(), $token->canonicalizationVersion())) {
            throw TokenContextMismatchException::create();
        }
    }

    private function context(TokenDomain $domain, CanonicalMessage $message, HmacKeyMaterial $key): string
    {
        $parts = [
            'protocol' => 'legacy-migration-hmac-v1',
            'environment' => $this->environment,
            'domain' => $domain->value(),
            'key_id' => $key->keyId(),
            'key_version' => $key->version(),
            'canonicalization_version' => $message->version(),
        ];
        $encoded = '';
        foreach ($parts as $name => $value) {
            $encoded .= pack('N', strlen($name)).$name.pack('N', strlen($value)).$value;
        }

        return $encoded;
    }
}
