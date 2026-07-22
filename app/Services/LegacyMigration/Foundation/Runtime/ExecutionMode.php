<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

enum ExecutionMode: string
{
    case DryRun = 'dry_run';
    case Commit = 'commit';
}
