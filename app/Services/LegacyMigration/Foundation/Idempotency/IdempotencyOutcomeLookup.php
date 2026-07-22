<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

interface IdempotencyOutcomeLookup
{
    public function find(string $domain, string $token): ?StoredIdempotencyOutcome;
}
