<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class PinnedProtectedRecordRotationAuthority implements ProtectedRecordRotationAuthority
{
    /** @param list<string> $approvedReasons */
    public function __construct(
        private readonly HmacTokenService $tokens,
        private readonly ProtectedRecordEnvelopeFactory $activeFactory,
        private readonly ProtectedRecordEnvelopeVerifier $activeVerifier,
        private readonly string $authorityReference,
        private readonly array $approvedReasons,
    ) {
        if ($this->authorityReference === '' || $this->approvedReasons === []) {
            throw SecurityConfigurationException::forCode('LM-SEC-STORE-ROTATION-AUTHORITY-001');
        }
    }

    public function approve(
        ProtectedStoreOperationContext $context,
        ProtectedRecordEnvelope $oldEnvelope,
        array $canonicalMessages,
        string $reason,
    ): ApprovedProtectedRotation {
        $oldTokenSet = $oldEnvelope->tokenSet;
        ksort($canonicalMessages, SORT_STRING);
        ksort($oldTokenSet, SORT_STRING);
        if (! hash_equals($this->authorityReference, $context->authorityReference())
            || ! in_array($reason, $this->approvedReasons, true)
            || array_keys($canonicalMessages) !== array_keys($oldTokenSet)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-AUTHORITY-001');
        }
        $newTokenSet = [];
        $primary = null;
        foreach ($oldTokenSet as $field => $entry) {
            $message = $canonicalMessages[$field] ?? null;
            if (! $message instanceof CanonicalMessage) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-MESSAGE-001');
            }
            $oldToken = ProtectedToken::parse($entry['encoded_token']);
            $rotation = $this->tokens->rotate(new TokenDomain($entry['domain']), $message, $oldToken);
            if (! $rotation->rotated) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-NOT-REQUIRED-001');
            }
            $encoded = $rotation->token->encode();
            $newTokenSet[$field] = ['encoded_token' => $encoded, 'domain' => $entry['domain']];
            if (hash_equals($oldEnvelope->encodedToken, $entry['encoded_token'])) {
                $primary = $encoded;
            }
        }
        if ($primary === null) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-PRIMARY-001');
        }

        return new ApprovedProtectedRotation($primary, $newTokenSet, $this->activeFactory, $this->activeVerifier, $this->authorityReference);
    }
}
