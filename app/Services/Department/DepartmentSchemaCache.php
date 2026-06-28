<?php

namespace App\Services\Department;

use Illuminate\Support\Facades\Schema;

/**
 * Per-process memoization of schema metadata. `Schema::hasTable()` /
 * `Schema::hasColumn()` each hit `information_schema`; the dashboard calls them
 * dozens of times per request (every scoped metric/queue/chart). The schema does
 * not change at runtime, so resolving each table/column once and reusing the
 * result removes the dominant share of dashboard queries.
 */
class DepartmentSchemaCache
{
    /** @var array<string, bool> */
    private static array $tables = [];

    /** @var array<string, bool> */
    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::$columns[$table.'.'.$column] ??= self::hasTable($table) && Schema::hasColumn($table, $column);
    }

    /** Reset the memoized metadata (used by tests that rebuild the schema). */
    public static function flush(): void
    {
        self::$tables = [];
        self::$columns = [];
    }
}
