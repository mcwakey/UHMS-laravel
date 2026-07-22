<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final class SchemaFingerprintService
{
    public function inspectSource(MetadataConnection $connection, string $database): SchemaObservation
    {
        $this->assertSnapshot($connection);
        $server = $this->one($connection->select(
            'SELECT DATABASE() AS database_name, VERSION() AS database_version, @@session.tx_read_only AS transaction_read_only'
        ));
        $this->assertObservedDatabase($server, $database);
        $this->assertReadOnly($server);

        $tables = $connection->select(
            'SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION, CREATE_OPTIONS FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$database],
        );
        $columns = $connection->select(
            'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [$database],
        );
        $indexes = $connection->select(
            'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY, SUB_PART, NULLABLE, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
            [$database],
        );
        $declaredConstraints = $connection->select(
            'SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? ORDER BY TABLE_NAME, CONSTRAINT_NAME',
            [$database],
        );
        $foreignKeyColumns = $connection->select(
            'SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION',
            [$database],
        );
        $referentialConstraints = $connection->select(
            'SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, UPDATE_RULE, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? ORDER BY TABLE_NAME, CONSTRAINT_NAME',
            [$database],
        );
        $indexesWithoutCardinality = array_map(static function (array $index): array {
            unset($index['CARDINALITY']);

            return $index;
        }, $indexes);

        return new SchemaObservation(
            connection: $connection->name(),
            database: (string) $server['database_name'],
            databaseVersion: (string) $server['database_version'],
            fingerprint: $this->hash([
                'approved_database' => $database,
                'tables' => $tables,
                'columns' => $columns,
                'indexes_without_cardinality' => $indexesWithoutCardinality,
                'declared_constraints' => $declaredConstraints,
                'foreign_key_columns' => $foreignKeyColumns,
                'referential_constraints' => $referentialConstraints,
            ]),
            tableCount: count($tables),
            columnCount: count($columns),
        );
    }

    public function inspectTarget(MetadataConnection $connection, string $database): SchemaObservation
    {
        $this->assertSnapshot($connection);
        $server = $this->one($connection->select(
            'SELECT DATABASE() AS database_name, VERSION() AS database_version, @@session.tx_read_only AS transaction_read_only'
        ));
        $this->assertObservedDatabase($server, $database);
        $this->assertReadOnly($server);

        $tables = $connection->select(
            'SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION, CREATE_OPTIONS, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$database],
        );
        $columns = $connection->select(
            'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_KEY, EXTRA, GENERATION_EXPRESSION, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [$database],
        );
        $constraints = $connection->select(
            'SELECT tc.TABLE_NAME, tc.CONSTRAINT_NAME, tc.CONSTRAINT_TYPE, kcu.ORDINAL_POSITION, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_SCHEMA, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE FROM information_schema.TABLE_CONSTRAINTS tc LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu ON kcu.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND kcu.TABLE_NAME = tc.TABLE_NAME AND kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND rc.TABLE_NAME = tc.TABLE_NAME AND rc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA = ? ORDER BY tc.TABLE_NAME, tc.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
            [$database],
        );
        $indexes = $connection->select(
            'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY, SUB_PART, NULLABLE, INDEX_TYPE, INDEX_COMMENT FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
            [$database],
        );
        $triggers = $connection->select(
            'SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_ORDER, ACTION_CONDITION, ACTION_STATEMENT, ACTION_ORIENTATION, ACTION_TIMING, SQL_MODE, CREATED, CHARACTER_SET_CLIENT, COLLATION_CONNECTION, DATABASE_COLLATION FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ? ORDER BY EVENT_OBJECT_TABLE, TRIGGER_NAME',
            [$database],
        );
        $events = $connection->select(
            'SELECT EVENT_NAME, EVENT_TYPE, EXECUTE_AT, INTERVAL_VALUE, INTERVAL_FIELD, STARTS, ENDS, STATUS, ON_COMPLETION, CREATED, LAST_ALTERED, LAST_EXECUTED, EVENT_COMMENT FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ? ORDER BY EVENT_NAME',
            [$database],
        );
        $routines = $connection->select(
            'SELECT ROUTINE_NAME, ROUTINE_TYPE, DATA_TYPE, IS_DETERMINISTIC, SQL_DATA_ACCESS, SECURITY_TYPE, CREATED, LAST_ALTERED, SQL_MODE, ROUTINE_COMMENT FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? ORDER BY ROUTINE_TYPE, ROUTINE_NAME',
            [$database],
        );
        $migrationTablePresent = in_array('migrations', array_column($tables, 'TABLE_NAME'), true);
        if (! $migrationTablePresent) {
            throw new FoundationGuardException('FOUNDATION_TARGET_LEDGER_MISSING', 'The target migration ledger is missing.');
        }
        $installedMigrations = $connection->select('SELECT migration, batch FROM `migrations` ORDER BY id');
        $indexesWithoutCardinality = array_map(static function (array $index): array {
            unset($index['CARDINALITY']);

            return $index;
        }, $indexes);

        return new SchemaObservation(
            connection: $connection->name(),
            database: (string) $server['database_name'],
            databaseVersion: (string) $server['database_version'],
            fingerprint: $this->hash([
                'database_name' => $database,
                'tables' => $tables,
                'columns' => $columns,
                'constraints' => $constraints,
                'indexes_without_cardinality' => $indexesWithoutCardinality,
                'triggers' => $triggers,
                'events' => $events,
                'routines' => $routines,
                'installed_migrations' => $installedMigrations,
            ]),
            tableCount: count($tables),
            columnCount: count($columns),
        );
    }

    /** @param array<string, mixed> $value */
    public function hash(array $value): string
    {
        return hash('sha256', json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));
    }

    private function assertSnapshot(MetadataConnection $connection): void
    {
        if (! $connection->readOnlySnapshotActive()) {
            throw new FoundationGuardException('FOUNDATION_SNAPSHOT_REQUIRED', 'A read-only metadata snapshot is required.');
        }
    }

    /** @param array<string, mixed> $server */
    private function assertObservedDatabase(array $server, string $database): void
    {
        if (! isset($server['database_name']) || ! is_string($server['database_name']) || ! hash_equals($database, $server['database_name'])) {
            throw new FoundationGuardException('FOUNDATION_DATABASE_MISMATCH', 'The selected database does not match the approved coordinate.');
        }
        if (! isset($server['database_version']) || ! is_string($server['database_version']) || $server['database_version'] === '') {
            throw new FoundationGuardException('FOUNDATION_VERSION_UNAVAILABLE', 'The database version could not be verified.');
        }
    }

    /** @param array<string, mixed> $server */
    private function assertReadOnly(array $server): void
    {
        if ((int) ($server['transaction_read_only'] ?? 0) !== 1) {
            throw new FoundationGuardException('FOUNDATION_READ_ONLY_UNPROVEN', 'The metadata transaction is not proven read-only.');
        }
    }

    /** @param array<int, array<string, mixed>> $rows @return array<string, mixed> */
    private function one(array $rows): array
    {
        if (count($rows) !== 1) {
            throw new FoundationGuardException('FOUNDATION_METADATA_UNAVAILABLE', 'Mandatory database metadata could not be verified.');
        }

        return $rows[0];
    }
}
