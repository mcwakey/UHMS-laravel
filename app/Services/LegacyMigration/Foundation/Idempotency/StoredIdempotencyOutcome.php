<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

use App\Services\LegacyMigration\Foundation\Recovery\MigrationState;

final readonly class StoredIdempotencyOutcome
{
    public function __construct(
        public IdempotencyKey $key,
        public MigrationState $state,
        public ?string $outcomeFingerprint,
        public bool $durableFactsComplete,
    ) {
        if ($outcomeFingerprint !== null && preg_match('/\A[0-9a-f]{64}\z/D', $outcomeFingerprint) !== 1) {
            throw IdempotencyException::incompatible();
        }
    }
}
