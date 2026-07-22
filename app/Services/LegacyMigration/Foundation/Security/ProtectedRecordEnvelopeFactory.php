<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class ProtectedRecordEnvelopeFactory
{
    public function __construct(private readonly ProtectedStoreAccessGuard $guard) {}

    public function create(
        ProtectedStoreOperationContext $context,
        string $operation,
        string $recordType,
        int $recordId,
        string $encodedToken,
        string $domain,
        ?int $runId,
        ?int $sourceSnapshotId,
        ?int $targetSnapshotId,
        string $accessClassification,
        string $retentionClassification,
        string $recordIntegrityReference,
        array $tokenSet,
    ): ProtectedRecordEnvelope {
        $this->guard->authorizeOperationContext($context, $operation, $domain);
        $context->assertAuthorized($operation, $domain);
        $context->assertCoordinates($runId, $sourceSnapshotId, $targetSnapshotId);
        $context->assertClassifications($accessClassification, $retentionClassification);
        $token = $this->guard->authorize($encodedToken, $domain);
        foreach ($tokenSet as $entry) {
            if (! is_array($entry) || ! is_string($entry['encoded_token'] ?? null) || ! is_string($entry['domain'] ?? null)) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-002');
            }
            $this->guard->authorize($entry['encoded_token'], $entry['domain']);
        }

        $draft = new ProtectedRecordEnvelope(
            $recordType,
            $recordId,
            $encodedToken,
            'unsealed',
            $tokenSet,
            $domain,
            $token->environment(),
            $token->keyId(),
            $token->keyVersion(),
            $token->canonicalizationVersion(),
            $recordIntegrityReference,
            $runId,
            $sourceSnapshotId,
            $targetSnapshotId,
            $accessClassification,
            $retentionClassification,
        );
        $seal = $this->guard->seal($encodedToken, $domain, $draft->integrityFields());

        return new ProtectedRecordEnvelope(
            $recordType,
            $recordId,
            $encodedToken,
            $seal->encode(),
            $tokenSet,
            $domain,
            $token->environment(),
            $token->keyId(),
            $token->keyVersion(),
            $token->canonicalizationVersion(),
            $recordIntegrityReference,
            $runId,
            $sourceSnapshotId,
            $targetSnapshotId,
            $accessClassification,
            $retentionClassification,
        );
    }

    /** @param list<TypedValue> $fields */
    public function keyedAuditChecksum(string $encodedToken, string $domain, array $fields): string
    {
        return $this->guard->seal($encodedToken, $domain, $fields)->lookupDigest();
    }

    /** @param list<TypedValue> $fields */
    public function keyedPurposeChecksum(ProtectedStoreOperationContext $context, string $operation, string $encodedToken, string $domain, array $fields): string
    {
        $this->guard->authorizeOperationContext($context, $operation, $domain);

        return $this->keyedAuditChecksum($encodedToken, $domain, $fields);
    }
}
