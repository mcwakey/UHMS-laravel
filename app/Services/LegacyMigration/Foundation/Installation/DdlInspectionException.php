<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use RuntimeException;

final class DdlInspectionException extends RuntimeException
{
    public function __construct(public readonly DdlInspectionState $state, string $safeMessage)
    {
        parent::__construct($safeMessage);
    }
}
