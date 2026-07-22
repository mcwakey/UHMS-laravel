<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class ProtectedRecordEnvelope
{
    public function __construct(
        public readonly string $recordType,
        public readonly int $recordId,
        public readonly string $encodedToken,
        public readonly string $encodedIntegritySeal,
        /** @var array<string, array{encoded_token:string, domain:string}> */
        public readonly array $tokenSet,
        public readonly string $domain,
        public readonly string $tokenEnvironment,
        public readonly string $keyId,
        public readonly string $keyVersion,
        public readonly string $canonicalizationVersion,
        public readonly string $recordIntegrityReference,
        public readonly ?int $runId,
        public readonly ?int $sourceSnapshotId,
        public readonly ?int $targetSnapshotId,
        public readonly string $accessClassification,
        public readonly string $retentionClassification,
    ) {
        if ($this->recordId < 1 || $this->recordType === '') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ENVELOPE-001');
        }
        if ($this->tokenSet === []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-001');
        }
    }

    /** @return list<TypedValue> */
    public function integrityFields(): array
    {
        $fields = [
            TypedValue::string($this->recordType),
            TypedValue::integer($this->recordId),
            TypedValue::string($this->domain),
            TypedValue::string($this->tokenEnvironment),
            TypedValue::string($this->keyId),
            TypedValue::string($this->keyVersion),
            TypedValue::string($this->canonicalizationVersion),
            TypedValue::string($this->recordIntegrityReference),
            $this->runId === null ? TypedValue::null() : TypedValue::integer($this->runId),
            $this->sourceSnapshotId === null ? TypedValue::null() : TypedValue::integer($this->sourceSnapshotId),
            $this->targetSnapshotId === null ? TypedValue::null() : TypedValue::integer($this->targetSnapshotId),
            TypedValue::string($this->accessClassification),
            TypedValue::string($this->retentionClassification),
        ];
        $tokenSet = $this->tokenSet;
        ksort($tokenSet, SORT_STRING);
        foreach ($tokenSet as $field => $token) {
            if (preg_match('/^[a-z][a-z0-9_]{1,95}$/D', $field) !== 1
                || ! is_array($token)
                || ! is_string($token['domain'] ?? null)
                || ! is_string($token['encoded_token'] ?? null)) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-TOKEN-SET-002');
            }
            $fields[] = TypedValue::string($field);
            $fields[] = TypedValue::string($token['domain']);
            $fields[] = TypedValue::string($token['encoded_token']);
        }

        return $fields;
    }
}
