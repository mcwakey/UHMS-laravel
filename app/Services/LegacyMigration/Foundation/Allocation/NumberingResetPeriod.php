<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

enum NumberingResetPeriod: string
{
    case Never = 'never';
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Daily = 'daily';
}
