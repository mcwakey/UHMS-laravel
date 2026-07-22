<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class ProtectedRecordEnvelopeVerifier
{
    public function __construct(private readonly ProtectedStoreAccessGuard $guard) {}

    public function authorize(ProtectedStoreOperationContext $context, string $operation, ProtectedRecordEnvelope $envelope): ProtectedToken
    {
        $this->guard->authorizeOperationContext($context, $operation, $envelope->domain);
        $context->assertAuthorized($operation, $envelope->domain);
        $context->assertCoordinates($envelope->runId, $envelope->sourceSnapshotId, $envelope->targetSnapshotId);
        $context->assertClassifications($envelope->accessClassification, $envelope->retentionClassification);
        if (! hash_equals($context->environment(), $envelope->tokenEnvironment)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONTEXT-ENVIRONMENT-001');
        }
        foreach ($envelope->tokenSet as $entry) {
            $this->guard->authorize($entry['encoded_token'], $entry['domain']);
        }

        $token = $this->guard->authorizeAndVerifyIntegrity(
            $envelope->encodedToken,
            $envelope->domain,
            $envelope->integrityFields(),
            $envelope->encodedIntegritySeal,
        );
        if (! hash_equals($envelope->tokenEnvironment, $token->environment())
            || ! hash_equals($envelope->keyId, $token->keyId())
            || ! hash_equals($envelope->keyVersion, $token->keyVersion())
            || ! hash_equals($envelope->canonicalizationVersion, $token->canonicalizationVersion())) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ENVELOPE-CONTEXT-001');
        }

        return $token;
    }
}
