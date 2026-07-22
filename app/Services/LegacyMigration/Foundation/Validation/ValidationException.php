<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /** @param array<int, string> $violationCodes */
    public function __construct(
        public readonly string $faultCode,
        public readonly array $violationCodes,
        string $safeMessage,
    ) {
        parent::__construct($safeMessage);
    }
}
