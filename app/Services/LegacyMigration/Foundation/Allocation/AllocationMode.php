<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

enum AllocationMode: string
{
    case DryRun = 'dry_run';
    case Commit = 'commit';
}
