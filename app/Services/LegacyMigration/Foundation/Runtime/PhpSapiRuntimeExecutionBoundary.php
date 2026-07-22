<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class PhpSapiRuntimeExecutionBoundary implements RuntimeExecutionBoundary
{
    public function isConsole(): bool
    {
        return PHP_SAPI === 'cli';
    }
}
