<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

final readonly class IdempotencyResolution
{
    public function __construct(
        public IdempotencyResolutionAction $action,
        public ?StoredIdempotencyOutcome $stored,
    ) {}
}
