<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

enum DdlObjectType: string
{
    case Table = 'table';
    case Column = 'column';
    case Index = 'index';
    case Constraint = 'constraint';
    case Trigger = 'trigger';
    case MigrationLedger = 'migration_ledger';
}
