<?php

namespace App\Services\LegacyMigration\Foundation\Security;

/**
 * Explicit, purpose-scoped authority for one protected-store operation.
 *
 * The context contains no raw identity values and is deliberately immutable.
 * It grants neither importer nor domain-write authority.
 */
final class ProtectedStoreOperationContext
{
    /** @var array<string, true> */
    private array $operations;

    /** @var array<string, true> */
    private array $domains;

    /** @var array<string, true> */
    private array $fields;

    /**
     * @param  list<string>  $operations
     * @param  list<string>  $domains
     * @param  list<string>  $authorizedFields
     */
    public function __construct(
        private readonly string $purpose,
        array $operations,
        array $domains,
        private readonly string $environment,
        private readonly ?int $runId,
        private readonly ?int $sourceSnapshotId,
        private readonly ?int $targetSnapshotId,
        private readonly string $accessClassification,
        private readonly string $retentionClassification,
        private readonly string $authorityReference,
        array $authorizedFields = [],
    ) {
        foreach ([$this->purpose, $this->environment, $this->accessClassification, $this->retentionClassification, $this->authorityReference] as $value) {
            if ($value === '' || strlen($value) > 160) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONTEXT-INVALID-001');
            }
        }

        $this->operations = $this->validatedSet($operations, '/^[a-z][a-z0-9._-]{1,63}$/D');
        $this->domains = $this->validatedSet($domains, '/^[a-z][a-z0-9_-]{2,95}$/D');
        $this->fields = $this->validatedSet($authorizedFields, '/^[a-z][a-z0-9_]{1,95}$/D', true);
    }

    public function assertAuthorized(string $operation, string $domain): void
    {
        if (! isset($this->operations[$operation])) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURPOSE-OPERATION-001');
        }
        if (! isset($this->domains[$domain])) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURPOSE-DOMAIN-001');
        }
    }

    public function assertCoordinates(?int $runId, ?int $sourceSnapshotId, ?int $targetSnapshotId): void
    {
        foreach ([[$this->runId, $runId], [$this->sourceSnapshotId, $sourceSnapshotId], [$this->targetSnapshotId, $targetSnapshotId]] as [$expected, $observed]) {
            if ($expected !== null && $expected !== $observed) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-COORDINATE-001');
            }
        }
    }

    public function assertClassifications(string $access, string $retention): void
    {
        if (! hash_equals($this->accessClassification, $access) || ! hash_equals($this->retentionClassification, $retention)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CLASSIFICATION-001');
        }
    }

    /** @return list<string> */
    public function authorizedFields(): array
    {
        return array_keys($this->fields);
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function runId(): ?int
    {
        return $this->runId;
    }

    public function sourceSnapshotId(): ?int
    {
        return $this->sourceSnapshotId;
    }

    public function targetSnapshotId(): ?int
    {
        return $this->targetSnapshotId;
    }

    public function accessClassification(): string
    {
        return $this->accessClassification;
    }

    public function retentionClassification(): string
    {
        return $this->retentionClassification;
    }

    public function authorityReference(): string
    {
        return $this->authorityReference;
    }

    /** @param list<string> $values @return array<string, true> */
    private function validatedSet(array $values, string $pattern, bool $mayBeEmpty = false): array
    {
        $set = [];
        foreach ($values as $value) {
            if (! is_string($value) || preg_match($pattern, $value) !== 1 || isset($set[$value])) {
                throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONTEXT-INVALID-002');
            }
            $set[$value] = true;
        }
        if (! $mayBeEmpty && $set === []) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-CONTEXT-INVALID-003');
        }

        return $set;
    }
}
