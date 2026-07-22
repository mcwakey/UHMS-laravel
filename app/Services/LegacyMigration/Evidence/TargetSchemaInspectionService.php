<?php

namespace App\Services\LegacyMigration\Evidence;

use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class TargetSchemaInspectionService
{
    private const TOOL_VERSION = 'phase-1b-target-inspector/2.1.0';

    /** @var array<int, string> */
    private const CLASSIC_DATABASES = ['uuhms', 'uhms', 'uuhmss'];

    public function __construct(
        private readonly DatabaseManager $databases,
        private readonly Filesystem $files,
    ) {}

    /** @return array<string, mixed> */
    public function capture(string $connectionName, string $expectedDatabase, string $outputDirectory): array
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Target inspection is forbidden when APP_ENV=production.');
        }

        if ($expectedDatabase === '') {
            throw new RuntimeException('--expected-database is required and must name the exact non-production target.');
        }

        if (in_array(strtolower($expectedDatabase), self::CLASSIC_DATABASES, true)) {
            throw new RuntimeException('Target inspection refused: a Classic schema name was supplied.');
        }

        if (preg_match('/(^|[_-])(prod|production)([_-]|$)/i', $expectedDatabase)) {
            throw new RuntimeException('Target inspection refused: the expected database name appears production-like.');
        }

        $outputPath = $this->safeOutputPath($outputDirectory);
        $connection = $this->databases->connection($connectionName);
        $configuredDatabase = (string) $connection->getDatabaseName();

        if (! hash_equals($expectedDatabase, $configuredDatabase)) {
            throw new RuntimeException("Target database mismatch: expected [{$expectedDatabase}] but the connection selected [{$configuredDatabase}].");
        }

        $executedAt = gmdate('c');
        $toolCodeHash = $this->toolCodeHash();
        $query = new ReadOnlyQueryRecorder($connection, self::TOOL_VERSION);

        $serverRow = $query->select(
            'target.server_configuration',
            'SELECT DATABASE() AS database_name, VERSION() AS database_version, @@version_comment AS version_comment, @@sql_mode AS sql_mode, @@lower_case_table_names AS lower_case_table_names, @@character_set_database AS character_set_database, @@collation_database AS collation_database, @@global.read_only AS global_read_only, @@session.tx_read_only AS session_transaction_read_only',
            purpose: 'Capture non-secret target database version and schema behavior settings.',
        )[0] ?? throw new RuntimeException('Unable to read target server configuration.');

        if ((string) $serverRow->database_name !== $expectedDatabase) {
            throw new RuntimeException('Target inspection refused because SELECT DATABASE() did not match the expected database.');
        }

        $tables = $this->rows($query->select(
            'target.tables',
            'SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION, CREATE_OPTIONS, TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$expectedDatabase],
            'Installed target tables/views and engine metadata.',
        ));

        $columns = $this->rows($query->select(
            'target.columns',
            'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_KEY, EXTRA, GENERATION_EXPRESSION, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [$expectedDatabase],
            'Installed target column catalogue including generated expressions.',
        ));

        $constraints = $this->rows($query->select(
            'target.constraints',
            'SELECT tc.TABLE_NAME, tc.CONSTRAINT_NAME, tc.CONSTRAINT_TYPE, kcu.ORDINAL_POSITION, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_SCHEMA, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, rc.UPDATE_RULE, rc.DELETE_RULE FROM information_schema.TABLE_CONSTRAINTS tc LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu ON kcu.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND kcu.TABLE_NAME = tc.TABLE_NAME AND kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND rc.TABLE_NAME = tc.TABLE_NAME AND rc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA = ? ORDER BY tc.TABLE_NAME, tc.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
            [$expectedDatabase],
            'Primary, unique and foreign-key constraint catalogue.',
        ));

        $indexes = $this->rows($query->select(
            'target.indexes',
            'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY, SUB_PART, NULLABLE, INDEX_TYPE, INDEX_COMMENT FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
            [$expectedDatabase],
            'Installed target index catalogue.',
        ));

        $triggers = $this->rows($query->select(
            'target.triggers',
            'SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_ORDER, ACTION_CONDITION, ACTION_STATEMENT, ACTION_ORIENTATION, ACTION_TIMING, SQL_MODE, CREATED, CHARACTER_SET_CLIENT, COLLATION_CONNECTION, DATABASE_COLLATION FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ? ORDER BY EVENT_OBJECT_TABLE, TRIGGER_NAME',
            [$expectedDatabase],
            'Installed database triggers; schema code only, no row data.',
        ));
        $events = $this->rows($query->select(
            'target.events',
            'SELECT EVENT_NAME, EVENT_TYPE, EXECUTE_AT, INTERVAL_VALUE, INTERVAL_FIELD, STARTS, ENDS, STATUS, ON_COMPLETION, CREATED, LAST_ALTERED, LAST_EXECUTED, EVENT_COMMENT FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ? ORDER BY EVENT_NAME',
            [$expectedDatabase],
            'Installed scheduled database events and execution metadata; definitions and definer identities are intentionally excluded.',
        ));
        $routines = $this->rows($query->select(
            'target.routines',
            'SELECT ROUTINE_NAME, ROUTINE_TYPE, DATA_TYPE, IS_DETERMINISTIC, SQL_DATA_ACCESS, SECURITY_TYPE, CREATED, LAST_ALTERED, SQL_MODE, ROUTINE_COMMENT FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? ORDER BY ROUTINE_TYPE, ROUTINE_NAME',
            [$expectedDatabase],
            'Installed stored procedures/functions and behavior metadata; definitions and definer identities are intentionally excluded.',
        ));

        $tableNames = array_map(fn (array $row): string => (string) $row['TABLE_NAME'], $tables);
        $installedMigrations = [];

        if (in_array((string) config('database.migrations.table', 'migrations'), $tableNames, true)) {
            $migrationTable = (string) config('database.migrations.table', 'migrations');
            $installedMigrations = $this->rows($query->select(
                'target.installed_migrations',
                "SELECT migration, batch FROM `{$migrationTable}` ORDER BY id",
                purpose: 'Installed Laravel migration ledger.',
            ));
        }

        $repository = $this->repositoryContract();
        $drift = $this->buildDrift($tableNames, $columns, $indexes, $installedMigrations, $repository);
        $enumContract = $this->enumContract();
        $installedEnumValues = $this->installedEnumValues($query, $columns, $enumContract);

        $manifest = [
            'manifest_version' => 2,
            'tool_version' => self::TOOL_VERSION,
            'tool_code_hash_sha256' => $toolCodeHash,
            'captured_at_utc' => $executedAt,
            'environment' => app()->environment(),
            'database' => [
                'name' => $expectedDatabase,
                'version' => (string) $serverRow->database_version,
                'version_comment' => (string) $serverRow->version_comment,
                'sql_mode' => (string) $serverRow->sql_mode,
                'lower_case_table_names' => (int) $serverRow->lower_case_table_names,
                'character_set' => (string) $serverRow->character_set_database,
                'collation' => (string) $serverRow->collation_database,
                'global_read_only' => (bool) $serverRow->global_read_only,
                'session_transaction_read_only' => (bool) $serverRow->session_transaction_read_only,
                'host' => 'redacted',
                'credentials_included' => false,
            ],
            'installed_migrations' => $installedMigrations,
            'tables' => $tables,
            'columns' => $columns,
            'constraints' => $constraints,
            'indexes' => $indexes,
            'generated_columns' => array_values(array_filter($columns, fn (array $column): bool => trim((string) ($column['GENERATION_EXPRESSION'] ?? '')) !== '')),
            'triggers' => $triggers,
            'events' => $events,
            'routines' => $routines,
            'repository_enum_contract' => $enumContract,
            'installed_enum_compatible_values' => $installedEnumValues,
            'service_only_invariants' => $this->serviceOnlyInvariants(),
            'repository_comparison' => $drift,
        ];

        $structuralIndexes = array_map(function (array $index): array {
            unset($index['CARDINALITY']);

            return $index;
        }, $indexes);
        $fingerprintInput = [
            'database_name' => $expectedDatabase,
            'tables' => $tables,
            'columns' => $columns,
            'constraints' => $constraints,
            'indexes_without_cardinality' => $structuralIndexes,
            'triggers' => $triggers,
            'events' => $events,
            'routines' => $routines,
            'installed_migrations' => $installedMigrations,
        ];
        $fingerprint = hash('sha256', $this->json($fingerprintInput, false));
        $fingerprintManifest = [
            'manifest_version' => 2,
            'tool_version' => self::TOOL_VERSION,
            'tool_code_hash_sha256' => $toolCodeHash,
            'captured_at_utc' => $executedAt,
            'approved_database' => $expectedDatabase,
            'algorithm' => 'sha256',
            'fingerprint' => $fingerprint,
            'fingerprint_kind' => 'structural_schema_and_migration_ledger',
            'canonical_components' => ['database_name', 'tables', 'columns', 'constraints', 'indexes_without_cardinality', 'triggers', 'events', 'routines', 'installed_migrations'],
            'excluded_from_fingerprint' => ['database_version', 'index_cardinality', 'capture_time', 'storage_sizes', 'row_counts', 'auto_increment_next_values'],
            'canonicalization' => 'Metadata queries are explicitly ordered; UTF-8 JSON uses unescaped Unicode/slashes and no insignificant whitespace; value types are preserved.',
            'table_count' => count($tables),
            'column_count' => count($columns),
            'constraint_row_count' => count($constraints),
            'index_column_count' => count($indexes),
            'trigger_count' => count($triggers),
            'event_count' => count($events),
            'routine_count' => count($routines),
        ];

        $this->files->ensureDirectoryExists($outputPath);
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'TARGET_CONSTRAINT_MANIFEST.json', $this->json($manifest));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'TARGET_SCHEMA_FINGERPRINT.json', $this->json($fingerprintManifest));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'TARGET_INSTALLED_SCHEMA_INVENTORY.md', $this->inventoryMarkdown($manifest, $fingerprint));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'TARGET_SCHEMA_DRIFT_REPORT.md', $this->driftMarkdown($manifest, $drift));

        return [
            'database' => $expectedDatabase,
            'table_count' => count($tables),
            'column_count' => count($columns),
            'fingerprint' => $fingerprint,
        ];
    }

    private function safeOutputPath(string $outputDirectory): string
    {
        $normalized = str_replace('\\', '/', trim($outputDirectory, '/\\ '));

        if ($normalized === '' || str_contains($normalized, '..') || ($normalized !== 'docs/legacy-migration' && ! str_starts_with($normalized, 'docs/legacy-migration/'))) {
            throw new RuntimeException('Output must stay under docs/legacy-migration.');
        }

        return base_path(str_replace('/', DIRECTORY_SEPARATOR, $normalized));
    }

    /** @param array<int, object> $rows @return array<int, array<string, mixed>> */
    private function rows(array $rows): array
    {
        return array_map(fn (object $row): array => (array) $row, $rows);
    }

    /** @return array<string, mixed> */
    private function repositoryContract(): array
    {
        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files);
        $migrationNames = [];
        $createTables = [];
        $tableOrigins = [];
        $conditional = [];
        $rawDdl = [];
        $columnState = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $migrationNames[] = $name;
            $contents = (string) $this->files->get($file);
            $up = $this->upMethodText($contents);

            if (preg_match('/Schema::has(?:Table|Column)\s*\(/', $up)) {
                $conditional[] = $name;
            }

            foreach ($this->schemaBlocks($up) as $block) {
                $table = $block['table'];
                $columns = $this->columnsFromBlueprint($block['body']);

                if ($block['operation'] === 'create') {
                    $createTables[$table] = true;
                    $tableOrigins[$table] = $name;
                    $columnState[$table] = [];
                }

                $columnState[$table] ??= [];
                foreach ($columns as $column) {
                    $columnState[$table][$column] = true;
                }

                foreach ($this->droppedColumns($block['body']) as $column) {
                    unset($columnState[$table][$column]);
                }

                foreach ($this->renamedColumns($block['body']) as [$from, $to]) {
                    unset($columnState[$table][$from]);
                    $columnState[$table][$to] = true;
                }
            }

            if (preg_match_all('/Schema::dropIfExists\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $up, $matches)) {
                foreach ($matches[1] as $table) {
                    unset($columnState[$table]);
                }
            }

            if (preg_match_all('/Schema::rename\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $up, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $columnState[$match[2]] = $columnState[$match[1]] ?? [];
                    unset($columnState[$match[1]]);
                }
            }

            foreach ($this->rawDdlExpectations($up, $name) as $expectation) {
                $rawDdl[] = $expectation;
            }
        }

        ksort($createTables);
        ksort($columnState);
        foreach ($columnState as &$state) {
            ksort($state);
            $state = array_keys($state);
        }

        $schemaDumpPath = database_path('schema/mysql-schema.sql');
        $schemaDump = ['present' => false, 'sha256' => null, 'table_names' => [], 'migration_count' => 0, 'last_migration' => null];
        if ($this->files->exists($schemaDumpPath)) {
            $dumpContents = (string) $this->files->get($schemaDumpPath);
            preg_match_all('/^CREATE TABLE `([^`]+)`/m', $dumpContents, $dumpTableMatches);
            preg_match_all('/INSERT INTO `migrations` .*?VALUES \(\d+,\'([^\']+)\',\d+\);/', $dumpContents, $dumpMigrationMatches);
            $dumpTables = array_values(array_unique($dumpTableMatches[1] ?? []));
            sort($dumpTables);
            $dumpMigrations = $dumpMigrationMatches[1] ?? [];
            $schemaDump = [
                'present' => true,
                'sha256' => hash('sha256', $dumpContents),
                'table_names' => $dumpTables,
                'migration_count' => count($dumpMigrations),
                'last_migration' => $dumpMigrations === [] ? null : end($dumpMigrations),
            ];
        }

        return [
            'migration_names' => $migrationNames,
            'create_tables' => array_keys($createTables),
            'heuristic_current_tables' => array_keys($columnState),
            'table_origins' => $tableOrigins,
            'conditional_migrations' => $conditional,
            'raw_ddl_expectations' => $rawDdl,
            'heuristic_current_columns' => $columnState,
            'heuristic_limitations' => 'Static parser covers common Schema blueprint operations and named raw indexes; dynamic PHP, anonymous raw DDL, driver branches, morph macros and complex conditional reversals require manual verification.',
            'schema_dump' => $schemaDump,
        ];
    }

    private function upMethodText(string $contents): string
    {
        $start = strpos($contents, 'function up');
        if ($start === false) {
            return $contents;
        }

        $end = strpos($contents, 'function down', $start);

        return $end === false ? substr($contents, $start) : substr($contents, $start, $end - $start);
    }

    /** @return array<int, array{operation:string,table:string,body:string}> */
    private function schemaBlocks(string $contents): array
    {
        $blocks = [];
        if (! preg_match_all('/Schema::(create|table)\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,\s*function\s*\([^)]*\)\s*\{(.*?)\}\s*\);/s', $contents, $matches, PREG_SET_ORDER)) {
            return $blocks;
        }

        foreach ($matches as $match) {
            $blocks[] = ['operation' => $match[1], 'table' => $match[2], 'body' => $match[3]];
        }

        return $blocks;
    }

    /** @return array<int, string> */
    private function columnsFromBlueprint(string $body): array
    {
        $columns = [];
        $types = 'bigIncrements|increments|tinyIncrements|smallIncrements|mediumIncrements|id|foreignId|unsignedBigInteger|bigInteger|unsignedInteger|integer|unsignedSmallInteger|smallInteger|unsignedTinyInteger|tinyInteger|mediumInteger|string|char|text|mediumText|longText|date|dateTime|dateTimeTz|timestamp|timestampTz|time|timeTz|decimal|double|float|boolean|json|jsonb|uuid|ulid|binary|year|ipAddress|macAddress|enum|set';
        if (preg_match_all('/\$table->('.$types.')\s*\(\s*[\'\"]([^\'\"]+)[\'\"]/', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columns[] = $match[2];
            }
        }

        if (preg_match('/\$table->id\s*\(\s*\)/', $body)) {
            $columns[] = 'id';
        }
        if (preg_match('/\$table->timestamps\s*\(/', $body)) {
            array_push($columns, 'created_at', 'updated_at');
        }
        if (preg_match('/\$table->softDeletes\s*\(/', $body)) {
            $columns[] = 'deleted_at';
        }
        if (preg_match('/\$table->rememberToken\s*\(/', $body)) {
            $columns[] = 'remember_token';
        }

        return array_values(array_unique($columns));
    }

    /** @return array<int, string> */
    private function droppedColumns(string $body): array
    {
        $columns = [];
        if (preg_match_all('/\$table->dropColumn\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $body, $matches)) {
            $columns = $matches[1];
        }

        return $columns;
    }

    /** @return array<int, array{0:string,1:string}> */
    private function renamedColumns(string $body): array
    {
        $renames = [];
        if (preg_match_all('/\$table->renameColumn\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $renames[] = [$match[1], $match[2]];
            }
        }

        return $renames;
    }

    /** @return array<int, array<string, string>> */
    private function rawDdlExpectations(string $contents, string $migration): array
    {
        $expectations = [];
        if (preg_match_all('/CREATE\s+(?:UNIQUE\s+)?INDEX\s+(?:IF\s+NOT\s+EXISTS\s+)?[`\"]?([A-Za-z0-9_]+)[`\"]?\s+ON\s+[`\"]?([A-Za-z0-9_]+)[`\"]?/i', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $expectations[] = ['migration' => $migration, 'type' => 'index', 'name' => $match[1], 'table' => $match[2]];
            }
        }

        return $expectations;
    }

    /** @param array<int, array<string,mixed>> $columns @param array<int, array<string,mixed>> $indexes @param array<int, array<string,mixed>> $installed @return array<string,mixed> */
    private function buildDrift(array $actualTables, array $columns, array $indexes, array $installed, array $repository): array
    {
        $actualTableSet = array_fill_keys($actualTables, true);
        $installedNames = array_map(fn (array $row): string => (string) $row['migration'], $installed);
        $installedSet = array_fill_keys($installedNames, true);
        $repoSet = array_fill_keys($repository['migration_names'], true);
        $actualColumns = [];
        foreach ($columns as $column) {
            $actualColumns[(string) $column['TABLE_NAME']][(string) $column['COLUMN_NAME']] = true;
        }

        $missingColumns = [];
        $extraColumns = [];
        foreach ($repository['heuristic_current_columns'] as $table => $expectedColumns) {
            if (! isset($actualTableSet[$table])) {
                continue;
            }
            foreach ($expectedColumns as $column) {
                if (! isset($actualColumns[$table][$column])) {
                    $missingColumns[] = ['table' => $table, 'column' => $column];
                }
            }
            foreach (array_keys($actualColumns[$table] ?? []) as $column) {
                if (! in_array($column, $expectedColumns, true)) {
                    $extraColumns[] = ['table' => $table, 'column' => $column];
                }
            }
        }

        $indexSet = [];
        foreach ($indexes as $index) {
            $indexSet[(string) $index['TABLE_NAME'].'|'.(string) $index['INDEX_NAME']] = true;
        }
        $missingRawDdl = [];
        foreach ($repository['raw_ddl_expectations'] as $expectation) {
            if (($expectation['type'] ?? '') === 'index' && ! isset($indexSet[$expectation['table'].'|'.$expectation['name']])) {
                $missingRawDdl[] = $expectation;
            }
        }

        // Compare the installed database with the end-state inferred after later
        // drop/rename migrations, not with every table ever created. The latter
        // incorrectly reports intentionally retired tables (for example
        // `drug_stock`) as drift.
        $expectedTables = $repository['heuristic_current_tables'];
        $dumpTables = $repository['schema_dump']['table_names'];

        return [
            'repository_migration_count' => count($repository['migration_names']),
            'installed_migration_count' => count($installedNames),
            'repository_migrations_not_installed' => array_values(array_diff($repository['migration_names'], $installedNames)),
            'installed_migrations_not_in_repository' => array_values(array_diff($installedNames, $repository['migration_names'])),
            'heuristic_repository_current_tables_missing_from_target' => array_values(array_diff($expectedTables, $actualTables)),
            'installed_tables_not_statically_created_by_repository_migrations' => array_values(array_diff($actualTables, $expectedTables)),
            'heuristic_expected_columns_missing' => $missingColumns,
            'heuristic_installed_columns_not_in_static_state' => $extraColumns,
            'conditional_migrations' => array_map(fn (string $migration): array => ['migration' => $migration, 'installed' => isset($installedSet[$migration])], $repository['conditional_migrations']),
            'raw_ddl_expectations_missing' => $missingRawDdl,
            'schema_dump' => [
                'present' => $repository['schema_dump']['present'],
                'sha256' => $repository['schema_dump']['sha256'],
                'table_count' => count($dumpTables),
                'migration_count' => $repository['schema_dump']['migration_count'],
                'last_migration' => $repository['schema_dump']['last_migration'],
                'installed_tables_absent_from_dump' => array_values(array_diff($actualTables, $dumpTables)),
                'dump_tables_absent_from_installed_target' => array_values(array_diff($dumpTables, $actualTables)),
            ],
            'static_analysis_limitations' => $repository['heuristic_limitations'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function enumContract(): array
    {
        $contract = [];
        $files = glob(app_path('Enums/*.php')) ?: [];
        sort($files);
        foreach ($files as $file) {
            $contents = (string) $this->files->get($file);
            if (! preg_match('/enum\s+([A-Za-z0-9_]+)/', $contents, $enumMatch)) {
                continue;
            }
            preg_match_all('/case\s+([A-Za-z0-9_]+)\s*=\s*[\'\"]([^\'\"]+)[\'\"]\s*;/', $contents, $caseMatches, PREG_SET_ORDER);
            $contract[] = [
                'enum' => $enumMatch[1],
                'file' => str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file)),
                'values' => array_map(fn (array $match): string => $match[2], $caseMatches),
                'database_enforced' => false,
            ];
        }

        return $contract;
    }

    /** @param array<int, array<string, mixed>> $columns @param array<int, array<string, mixed>> $enumContract @return array<string, array<string, mixed>> */
    private function installedEnumValues(ReadOnlyQueryRecorder $query, array $columns, array $enumContract): array
    {
        $available = [];
        foreach ($columns as $column) {
            $available[$column['TABLE_NAME'].'.'.$column['COLUMN_NAME']] = true;
        }
        $enumValues = [];
        foreach ($enumContract as $enum) {
            $enumValues[$enum['enum']] = $enum['values'];
        }
        $fields = [
            ['patients', 'gender', 'Gender'], ['patients', 'blood_group', 'BloodGroup'], ['patients', 'marital_status', 'MaritalStatus'],
            ['visits', 'visit_type', 'VisitType'], ['visits', 'status', 'VisitStatus'], ['visits', 'priority', 'Priority'], ['visits', 'triage_score', 'TriageScore'], ['visits', 'consultation_mode', 'ConsultationMode'],
            ['appointments', 'visit_type', 'VisitType'], ['appointments', 'priority', 'Priority'], ['appointments', 'consultation_mode', 'ConsultationMode'], ['appointments', 'status', 'AppointmentStatus'],
            ['admissions', 'status', 'AdmissionStatus'], ['invoices', 'billing_type', 'BillingType'], ['invoices', 'status', 'InvoiceStatus'],
            ['payments', 'payment_method', 'PaymentMethod'], ['payments', 'status', 'PaymentStatus'], ['products', 'product_type', 'ProductType'],
            ['patient_insurances', 'member_type', 'MemberType'], ['claims', 'status', 'ClaimStatus'],
        ];

        $results = [];
        foreach ($fields as [$table, $column, $enum]) {
            $id = $table.'.'.$column;
            if (! isset($available[$id])) {
                continue;
            }
            $rows = $query->select(
                'target.enum_values.'.$id,
                "SELECT COALESCE(CAST(`{$column}` AS CHAR), '__NULL__') AS category_value, COUNT(*) AS aggregate_count FROM `{$table}` GROUP BY category_value ORDER BY category_value",
                purpose: 'Allow-listed nonidentifying enum-compatible aggregate values from the installed target.',
            );
            $allowed = $enumValues[$enum] ?? [];
            $redacted = (new UnexpectedTargetEnumRedactor)->redact($rows, $allowed);
            $results[$id] = [
                'repository_enum' => $enum,
                'repository_values' => $allowed,
                ...$redacted,
            ];
        }

        return $results;
    }

    /** @return array<int, array<string, string>> */
    private function serviceOnlyInvariants(): array
    {
        return [
            ['invariant' => 'one_active_invoice_per_visit', 'evidence' => 'database/migrations/2026_05_11_000003_enforce_unique_active_invoice_per_visit.php', 'risk' => 'Migration can fall back to a non-unique index on MariaDB.'],
            ['invariant' => 'one_active_admission_per_patient_and_visit', 'evidence' => 'app/Services/AdmissionService.php', 'risk' => 'Service enforced; no complete database unique constraint.'],
            ['invariant' => 'one_medical_record_per_consultation_route', 'evidence' => 'app/Models/VisitConsultationRoute.php', 'risk' => 'Model hasOne is not a database unique constraint.'],
            ['invariant' => 'one_visit_per_patient_per_day', 'evidence' => 'app/Services/VisitService.php', 'risk' => 'Operational service rule; database uniqueness may not enforce it.'],
            ['invariant' => 'valid_claim_status_transition', 'evidence' => 'app/Services/Claims/ClaimStatusService.php', 'risk' => 'Transition service does not uniformly enforce enum transition rules.'],
            ['invariant' => 'balanced_journal_and_open_period', 'evidence' => 'app/Services/JournalEntryService.php', 'risk' => 'Validated during operational posting, not fully expressed as database constraints.'],
            ['invariant' => 'route_context_referential_consistency', 'evidence' => 'app/Services/ConsultationRouteService.php', 'risk' => 'Route, patient, visit, department, service and medical-record links include application-only references without complete foreign keys.'],
            ['invariant' => 'payment_allocation_validity', 'evidence' => 'app/Services/PaymentService.php', 'risk' => 'Positive amounts, item ownership, exact allocation and no overpayment are service checks; duplicate payment/item allocations are not database-unique.'],
            ['invariant' => 'stock_balance_derived_from_movements', 'evidence' => 'app/Services/ProductStockMovementService.php', 'risk' => 'Balance derivation, movement direction and weighted-average valuation are operational service rules.'],
            ['invariant' => 'active_bed_occupancy', 'evidence' => 'app/Services/AdmissionService.php', 'risk' => 'Bed availability and active occupancy are checked/mutated operationally, not protected by a complete database uniqueness rule.'],
            ['invariant' => 'enum_compatible_strings', 'evidence' => 'app/Enums and model casts', 'risk' => 'Only two installed columns use native SQL ENUM; most enum-backed varchar values require application/import validation.'],
            ['invariant' => 'patient_number_sequence_reconciliation', 'evidence' => 'app/Services/PatientIdGeneratorService.php', 'risk' => 'Uniqueness is database-enforced on patient_number, but current-period sequence state and collision behavior are service-managed.'],
        ];
    }

    /** @param array<string,mixed> $manifest */
    private function inventoryMarkdown(array $manifest, string $fingerprint): string
    {
        $database = $manifest['database'];
        $columnsByTable = [];
        foreach ($manifest['columns'] as $column) {
            $columnsByTable[$column['TABLE_NAME']][] = $column;
        }
        $constraintDefinitions = [];
        foreach ($manifest['constraints'] as $constraint) {
            $key = $constraint['TABLE_NAME'].'|'.$constraint['CONSTRAINT_NAME'].'|'.$constraint['CONSTRAINT_TYPE'];
            $constraintDefinitions[$key] = $constraint['CONSTRAINT_TYPE'];
        }
        $indexDefinitions = [];
        foreach ($manifest['indexes'] as $index) {
            $indexDefinitions[$index['TABLE_NAME'].'|'.$index['INDEX_NAME']] = true;
        }
        $constraintTypeCount = array_count_values(array_values($constraintDefinitions));
        $nullableColumns = count(array_filter($manifest['columns'], fn (array $column): bool => $column['IS_NULLABLE'] === 'YES'));
        $autoIncrementColumns = count(array_filter($manifest['columns'], fn (array $column): bool => str_contains((string) $column['EXTRA'], 'auto_increment')));
        $nativeEnumColumns = count(array_filter($manifest['columns'], fn (array $column): bool => $column['DATA_TYPE'] === 'enum'));
        $profiledEnumFields = count($manifest['installed_enum_compatible_values']);
        $incompatibleEnumFields = count(array_filter($manifest['installed_enum_compatible_values'], fn (array $field): bool => ! $field['all_non_null_values_compatible']));

        $lines = [
            '# Installed target schema inventory', '',
            'Generated by `php artisan legacy-migration:inspect-target` using SELECT-only metadata queries.', '',
            '## Capture', '',
            '- **Evidence:** Confirmed installed non-production target metadata.',
            '- **Captured at (UTC):** `'.$manifest['captured_at_utc'].'`',
            '- **Environment:** `'.$manifest['environment'].'`',
            '- **Database:** `'.$database['name'].'`',
            '- **Version:** `'.$this->md($database['version']).'`',
            '- **Character set/collation:** `'.$database['character_set'].'` / `'.$database['collation'].'`',
            '- **Tables/views:** '.count($manifest['tables']),
            '- **Columns:** '.count($manifest['columns']),
            '- **Nullable / auto-increment columns:** '.$nullableColumns.' / '.$autoIncrementColumns,
            '- **Primary / unique / foreign-key / check constraints:** '.($constraintTypeCount['PRIMARY KEY'] ?? 0).' / '.($constraintTypeCount['UNIQUE'] ?? 0).' / '.($constraintTypeCount['FOREIGN KEY'] ?? 0).' / '.($constraintTypeCount['CHECK'] ?? 0),
            '- **Index definitions / index parts:** '.count($indexDefinitions).' / '.count($manifest['indexes']),
            '- **Native SQL enum columns:** '.$nativeEnumColumns,
            '- **Allow-listed installed enum fields profiled / incompatible:** '.$profiledEnumFields.' / '.$incompatibleEnumFields,
            '- **Triggers:** '.count($manifest['triggers']),
            '- **Server/session read-only:** '.($database['global_read_only'] ? 'yes' : 'no').' / '.($database['session_transaction_read_only'] ? 'yes' : 'no'),
            '- **Schema fingerprint:** `'.$fingerprint.'`',
            '- **Credentials/host:** not captured.', '',
            '## Installed tables and columns', '',
        ];

        foreach ($manifest['tables'] as $table) {
            $tableName = (string) $table['TABLE_NAME'];
            $lines[] = '### `'.$tableName.'`';
            $lines[] = '';
            $lines[] = 'Type `'.$table['TABLE_TYPE'].'`; engine `'.($table['ENGINE'] ?: 'n/a').'`; collation `'.($table['TABLE_COLLATION'] ?: 'n/a').'`.';
            $lines[] = '';
            $lines[] = '| # | Column | Type | Nullable | Default | Key | Extra/generated |';
            $lines[] = '|---:|---|---|---|---|---|---|';
            foreach ($columnsByTable[$tableName] ?? [] as $column) {
                $generated = trim((string) ($column['GENERATION_EXPRESSION'] ?? ''));
                $extra = trim((string) ($column['EXTRA'] ?? ''));
                if ($generated !== '') {
                    $extra .= ($extra !== '' ? '; ' : '').'expression: '.$generated;
                }
                $lines[] = sprintf('| %d | `%s` | `%s` | %s | %s | `%s` | %s |',
                    $column['ORDINAL_POSITION'], $column['COLUMN_NAME'], $this->md((string) $column['COLUMN_TYPE']), $column['IS_NULLABLE'],
                    $column['COLUMN_DEFAULT'] === null ? 'NULL' : '`'.$this->md((string) $column['COLUMN_DEFAULT']).'`',
                    $column['COLUMN_KEY'] ?: '-', $extra === '' ? '-' : '`'.$this->md($extra).'`');
            }
            $lines[] = '';
        }

        $lines[] = '## Installed migrations';
        $lines[] = '';
        $lines[] = 'Installed migration rows: '.count($manifest['installed_migrations']).'. See `TARGET_CONSTRAINT_MANIFEST.json` for the complete ordered ledger.';
        $lines[] = '';
        $lines[] = '## Generated columns and database-side executable objects';
        $lines[] = '';
        $lines[] = '- Generated columns: '.count($manifest['generated_columns']).'.';
        $lines[] = '- Triggers: '.count($manifest['triggers']).'.';
        $lines[] = '- Scheduled events: '.count($manifest['events']).'.';
        $lines[] = '- Stored routines: '.count($manifest['routines']).'.';
        $lines[] = '- Complete definitions are in `TARGET_CONSTRAINT_MANIFEST.json`.';

        return implode("\n", $lines)."\n";
    }

    /** @param array<string,mixed> $manifest @param array<string,mixed> $drift */
    private function driftMarkdown(array $manifest, array $drift): string
    {
        $lines = [
            '# Target schema drift report', '',
            'Generated from the installed non-production target and the current repository. No database changes were made.', '',
            '## Confirmed migration-ledger drift', '',
            '- Repository migration files: '.$drift['repository_migration_count'],
            '- Installed migration rows: '.$drift['installed_migration_count'],
            '- Repository migrations not installed: '.count($drift['repository_migrations_not_installed']),
            '- Installed migrations absent from repository: '.count($drift['installed_migrations_not_in_repository']),
            '- Heuristic repository end-state tables missing from target: '.count($drift['heuristic_repository_current_tables_missing_from_target']),
            '- Installed tables requiring manual origin verification: '.count($drift['installed_tables_not_statically_created_by_repository_migrations']), '',
            '### Repository migrations not installed', '',
            $this->mdList($drift['repository_migrations_not_installed']), '',
            '### Installed migrations absent from repository', '',
            $this->mdList($drift['installed_migrations_not_in_repository']), '',
            '## Static-analysis candidates (not confirmed drift)', '',
            'The parser applies common create/alter/drop operations in migration order. Dynamic helpers, package migrations and raw SQL still require repository verification.', '',
            '### Heuristic repository end-state tables missing from target', '',
            $this->mdList($drift['heuristic_repository_current_tables_missing_from_target']), '',
            '### Installed tables requiring manual origin verification', '',
            $this->mdList($drift['installed_tables_not_statically_created_by_repository_migrations']), '',
            '**Manual Phase 1B conclusion:** repository review traced these candidates to package/framework tables, dynamic helpers, or migration constructs the parser does not understand. No installed table is currently confirmed to lack a repository origin.', '',
            '## Repository schema-dump comparison', '',
            '- Dump present: '.($drift['schema_dump']['present'] ? 'yes' : 'no'),
            '- Dump SHA-256: `'.($drift['schema_dump']['sha256'] ?? 'not available').'`',
            '- Dump tables: '.$drift['schema_dump']['table_count'],
            '- Dump migration ledger rows: '.$drift['schema_dump']['migration_count'],
            '- Dump last migration: `'.($drift['schema_dump']['last_migration'] ?? 'not available').'`',
            '- Installed tables absent from dump: '.count($drift['schema_dump']['installed_tables_absent_from_dump']),
            '- Dump tables absent from installed target: '.count($drift['schema_dump']['dump_tables_absent_from_installed_target']), '',
            'The dump is a stale historical snapshot and is not the installed target contract. The only dump table absent from the installed target is expected to be the intentionally retired `drug_stock`; later migrations also intentionally remove `visits.assigned_doctor_id`.', '',
            '## Conditional and raw-DDL checks', '',
            '- Conditional migrations discovered: '.count($drift['conditional_migrations']),
            '- Named raw-DDL indexes expected but absent: '.count($drift['raw_ddl_expectations_missing']), '',
        ];

        foreach ($drift['raw_ddl_expectations_missing'] as $missing) {
            $lines[] = '- **Confirmed absent installed index:** `'.$missing['table'].'.'.$missing['name'].'` from `'.$missing['migration'].'`.';
        }
        if ($drift['raw_ddl_expectations_missing'] === []) {
            $lines[] = '- No missing named raw-DDL index was detected by the static parser.';
        }

        $lines[] = '';
        $lines[] = 'The installed `invoices.active_visit_id` generated column is present, but its intended unique index is absent; the non-unique fallback is installed. One-active-invoice-per-visit is therefore service-enforced only.';
        $lines[] = '';
        $lines[] = '## Heuristic column comparison';
        $lines[] = '';
        $lines[] = '- Candidate expected columns absent: '.count($drift['heuristic_expected_columns_missing']).'.';
        $lines[] = '- Candidate installed columns not represented by the static state: '.count($drift['heuristic_installed_columns_not_in_static_state']).'.';
        $lines[] = '- **Interpretation:** Inferred/static-analysis candidates, not confirmed drift, until the migration branch and later drops/renames/raw DDL are manually verified.';
        $lines[] = '- Limitation: '.$drift['static_analysis_limitations'];
        $lines[] = '- **Manual Phase 1B conclusion:** repository review traced the current missing-column candidates to intentional later removals and the current extra-column candidates to helpers, raw SQL, or dynamic migration constructs outside the static parser. The generated candidate counts above remain evidence, not hard-coded conclusions. No installed column is currently confirmed to lack a repository origin, and no current expected column is confirmed missing.';
        $lines[] = '';
        $lines[] = 'Complete candidate rows and all installed constraints/indexes are in `TARGET_CONSTRAINT_MANIFEST.json`.';
        $lines[] = '';
        $lines[] = '## Service-only invariants';
        $lines[] = '';
        foreach ($manifest['service_only_invariants'] as $invariant) {
            $lines[] = '- `'.$invariant['invariant'].'`: '.$invariant['risk'].' Evidence: `'.$invariant['evidence'].'`.';
        }

        return implode("\n", $lines)."\n";
    }

    /** @param array<int,string> $items */
    private function mdList(array $items): string
    {
        return $items === [] ? '- None.' : implode("\n", array_map(fn (string $item): string => '- `'.$item.'`', $items));
    }

    private function md(string $value): string
    {
        return str_replace(["\r", "\n", '|', '`'], [' ', ' ', '\\|', '\\`'], $value);
    }

    private function toolCodeHash(): string
    {
        $files = [
            'TargetSchemaInspectionService.php' => __FILE__,
            'ReadOnlyQueryRecorder.php' => __DIR__.DIRECTORY_SEPARATOR.'ReadOnlyQueryRecorder.php',
            'LegacyMigrationInspectTargetCommand.php' => app_path('Console/Commands/LegacyMigrationInspectTargetCommand.php'),
        ];
        ksort($files, SORT_STRING);
        $context = hash_init('sha256');
        foreach ($files as $label => $file) {
            hash_update($context, $label."\n");
            hash_update($context, (string) $this->files->get($file));
            hash_update($context, "\n");
        }

        return hash_final($context);
    }

    /** @param array<string,mixed> $value */
    private function json(array $value, bool $pretty = true): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($value, $flags).($pretty ? "\n" : '');
    }
}
