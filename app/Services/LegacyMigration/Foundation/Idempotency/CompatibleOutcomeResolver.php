<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

use App\Services\LegacyMigration\Foundation\Recovery\MigrationState;

final readonly class CompatibleOutcomeResolver
{
    public function __construct(private IdempotencyOutcomeLookup $outcomes) {}

    public function resolve(IdempotencyKey $requested): IdempotencyResolution
    {
        $stored = $this->outcomes->find($requested->domain, $requested->token);
        if ($stored === null) {
            return new IdempotencyResolution(IdempotencyResolutionAction::Create, null);
        }

        $this->assertCompatible($requested, $stored->key);

        if ($stored->state === MigrationState::Completed) {
            if (! $stored->durableFactsComplete || $stored->outcomeFingerprint === null) {
                throw IdempotencyException::incompatible();
            }

            return new IdempotencyResolution(IdempotencyResolutionAction::ReuseCompleted, $stored);
        }

        if ($stored->state === MigrationState::ReconciliationPassed && $stored->durableFactsComplete) {
            return new IdempotencyResolution(IdempotencyResolutionAction::RepairTerminalMetadata, $stored);
        }

        if (in_array($stored->state, [
            MigrationState::ReconciliationFailed,
            MigrationState::CompensationRequired,
            MigrationState::RollbackRequired,
            MigrationState::Quarantined,
        ], true)) {
            throw IdempotencyException::incompatible();
        }

        return new IdempotencyResolution(IdempotencyResolutionAction::Resume, $stored);
    }

    private function assertCompatible(IdempotencyKey $requested, IdempotencyKey $stored): void
    {
        if ($requested->domain !== $stored->domain
            || ! hash_equals($requested->token, $stored->token)
            || $requested->outcomeType !== $stored->outcomeType
            || ! hash_equals($requested->inputFingerprint, $stored->inputFingerprint)
            || $requested->contractVersion !== $stored->contractVersion
            || $requested->transformationVersion !== $stored->transformationVersion
            || $requested->canonicalizationVersion !== $stored->canonicalizationVersion
            || $requested->hmacKeyVersion !== $stored->hmacKeyVersion) {
            throw IdempotencyException::incompatible();
        }
    }
}
