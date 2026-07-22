<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

use RuntimeException;

final class RecoveryException extends RuntimeException
{
    public static function failClosed(string $code): self
    {
        return new self("Migration recovery stopped [{$code}].");
    }
}
