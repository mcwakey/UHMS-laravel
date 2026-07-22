<?php

namespace App\Models\LegacyMigration\Concerns;

use LogicException;

trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Migration evidence is append-only and cannot be updated.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Migration evidence is append-only and cannot be deleted.');
        });
    }
}
