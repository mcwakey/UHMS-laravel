<?php

namespace App\Services\LegacyMigration\Foundation\Reconciliation;

enum ReconciliationVerdict: string
{
    case Passed = 'passed';
    case BlockedNotMeasured = 'blocked_not_measured';
    case FailedNonZeroDifference = 'failed_nonzero_difference';
    case FailedUnexplained = 'failed_unexplained';
}
