<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use RuntimeException;

final class SnapshotException extends RuntimeException
{
    public function __construct(
        public readonly string $faultCode,
        string $safeMessage,
    ) {
        parent::__construct($safeMessage);
    }
}
