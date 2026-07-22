<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use RuntimeException;

final class SecurityConfigurationException extends RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self("Legacy migration security configuration is invalid [{$code}].");
    }
}
