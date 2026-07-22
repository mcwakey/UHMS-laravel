<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use InvalidArgumentException;
use Throwable;

final class ProtectedStoreAccessGuard
{
    public function __construct(
        private readonly ?ProtectedStoreAccessAuthority $authority,
        private readonly HmacTokenService $tokens,
        private readonly CanonicalTypedMessageEncoder $encoder,
    ) {}

    public function authorize(string $encodedToken, string $requiredDomain): ProtectedToken
    {
        $authority = $this->authority();
        if (! in_array($requiredDomain, $authority->permittedDomains(), true)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-DOMAIN-001');
        }

        try {
            $token = ProtectedToken::parse($encodedToken);
        } catch (InvalidArgumentException) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-001');
        }

        $this->assertPinnedContext($token, $requiredDomain, $authority);

        return $token;
    }

    /**
     * Creates a token-bound keyed integrity seal for protected-store writes.
     *
     * @param  list<TypedValue>  $integrityFields
     */
    public function seal(string $encodedToken, string $requiredDomain, array $integrityFields): ProtectedToken
    {
        $token = $this->authorize($encodedToken, $requiredDomain);
        $message = $this->integrityMessage($token, $integrityFields);
        $seal = $this->tokens->tokenize(new TokenDomain($this->authority()->integrityDomain()), $message);
        $this->assertPinnedContext($seal, $this->authority()->integrityDomain(), $this->authority());

        return $seal;
    }

    /**
     * Authorizes access only after the protected token and its token-bound
     * integrity seal both validate against the pinned authority.
     *
     * @param  list<TypedValue>  $integrityFields
     */
    public function authorizeAndVerifyIntegrity(
        string $encodedToken,
        string $requiredDomain,
        array $integrityFields,
        string $encodedIntegritySeal,
    ): ProtectedToken {
        $token = $this->authorize($encodedToken, $requiredDomain);
        try {
            $seal = ProtectedToken::parse($encodedIntegritySeal);
        } catch (InvalidArgumentException) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-INTEGRITY-001');
        }

        $authority = $this->authority();
        $this->assertPinnedContext($seal, $authority->integrityDomain(), $authority);

        try {
            $valid = $this->tokens->verify(
                new TokenDomain($authority->integrityDomain()),
                $this->integrityMessage($token, $integrityFields),
                $seal,
            );
        } catch (Throwable) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-INTEGRITY-001');
        }
        if (! $valid) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-INTEGRITY-001');
        }

        return $token;
    }

    private function authority(): ProtectedStoreAccessAuthority
    {
        if ($this->authority === null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-AUTHORITY-MISSING-001');
        }

        return $this->authority;
    }

    private function assertPinnedContext(
        ProtectedToken $token,
        string $requiredDomain,
        ProtectedStoreAccessAuthority $authority,
    ): void {
        if (! hash_equals($authority->environment(), $token->environment())
            || ! hash_equals($requiredDomain, $token->domain())
            || ! hash_equals($authority->keyId(), $token->keyId())
            || ! hash_equals($authority->keyVersion(), $token->keyVersion())
            || ! hash_equals($authority->canonicalizationVersion(), $token->canonicalizationVersion())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONTEXT-001');
        }
    }

    /** @param list<TypedValue> $integrityFields */
    private function integrityMessage(ProtectedToken $token, array $integrityFields): CanonicalMessage
    {
        try {
            return $this->encoder->encode(
                [TypedValue::string($token->encode()), ...$integrityFields],
                $this->authority()->canonicalizationVersion(),
            );
        } catch (Throwable) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-INTEGRITY-INPUT-001');
        }
    }
}
