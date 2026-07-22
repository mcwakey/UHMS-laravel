<?php

namespace App\Services\LegacyMigration\Foundation\Idempotency;

enum IdempotencyResolutionAction: string
{
    case Create = 'create';
    case Resume = 'resume';
    case ReuseCompleted = 'reuse_completed';
    case RepairTerminalMetadata = 'repair_terminal_metadata';
}
