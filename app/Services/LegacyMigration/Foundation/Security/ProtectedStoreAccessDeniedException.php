<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use RuntimeException;

final class ProtectedStoreAccessDeniedException extends RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self("Protected migration store access denied [{$code}].");
    }
}
