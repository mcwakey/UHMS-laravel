<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use RuntimeException;
use Throwable;

final class RuntimeIsolationException extends RuntimeException
{
    public function __construct(
        public readonly string $faultCode,
        string $safeMessage,
        ?Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }
}
