<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

use RuntimeException;

final class IdempotencyException extends RuntimeException
{
    public static function incompatible(): self
    {
        return new self('Idempotency lineage is incompatible; execution stopped [PILOT-IDEM-CONFLICT].');
    }
}
