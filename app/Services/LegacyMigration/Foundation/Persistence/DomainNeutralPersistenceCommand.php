<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

final readonly class DomainNeutralPersistenceCommand
{
    /**
     * References must already be protected and fields must already be
     * classified. This object deliberately has no Classic connection or model.
     *
     * @param  array<string, string>  $protectedReferences
     * @param  array<int, string>  $classificationRuleIds
     */
    public function __construct(
        public PersistenceOperation $operation,
        public string $runToken,
        public string $idempotencyKey,
        public string $targetSnapshotId,
        public array $protectedReferences,
        public array $classificationRuleIds,
    ) {
        if (! self::isDigest($runToken) || ! self::isDigest($idempotencyKey) || ! self::isDigest($targetSnapshotId)) {
            throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_COMMAND_INVALID', 'A protected persistence command coordinate is invalid.');
        }
        if ($protectedReferences === [] || $classificationRuleIds === []) {
            throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_COMMAND_INCOMPLETE', 'A classified persistence command is incomplete.');
        }
        foreach ($protectedReferences as $name => $reference) {
            if (! is_string($name) || trim($name) === '' || ! is_string($reference) || ! self::isDigest($reference)) {
                throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_COMMAND_INVALID', 'A protected persistence command reference is invalid.');
            }
        }
        foreach ($classificationRuleIds as $ruleId) {
            if (! is_string($ruleId) || trim($ruleId) === '') {
                throw new PersistenceBoundaryException('FOUNDATION_PERSISTENCE_COMMAND_INVALID', 'A persistence classification reference is invalid.');
            }
        }
    }

    private static function isDigest(string $value): bool
    {
        return preg_match('/\A(?:sha256:)?[a-f0-9]{64}\z/i', $value) === 1;
    }
}
