<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

interface RuntimeExecutionBoundary
{
    public function isConsole(): bool;
}
