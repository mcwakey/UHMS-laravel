<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

use RuntimeException;

final class PersistenceBoundaryException extends RuntimeException
{
    public function __construct(
        public readonly string $faultCode,
        string $safeMessage,
    ) {
        parent::__construct($safeMessage);
    }
}
