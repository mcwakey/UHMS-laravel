<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

use RuntimeException;

final class FoundationGuardException extends RuntimeException
{
    public function __construct(
        public readonly string $faultCode,
        string $safeMessage,
    ) {
        parent::__construct($safeMessage);
    }
}
