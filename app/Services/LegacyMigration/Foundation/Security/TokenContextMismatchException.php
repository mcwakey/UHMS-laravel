<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use RuntimeException;

final class TokenContextMismatchException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Protected token context mismatch [LM-SEC-TOKEN-CONTEXT-001].');
    }
}
