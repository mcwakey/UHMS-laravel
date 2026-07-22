<?php

namespace App\Services\LegacyMigration\Evidence;

use App\Services\LegacyMigration\Foundation\Environment\SourceAccountVerification;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class ClassicEvidenceCaptureService
{
    private const TOOL_VERSION = 'phase-1b-classic-evidence/2.2.0';

    private const APPROVED_DATABASE = 'uuhms';

    private const APPROVED_CONNECTION = 'legacy_uhms';

    /**
     * Literal values reviewed as non-identifying. Values outside these lists are
     * represented by a digest and count, never emitted verbatim.
     *
     * @var array<string, array<int, string>>
     */
    private const SAFE_CATEGORY_LITERALS = [
        'attendance.AttStatus' => ['OUTPATIENT', 'INPATIENT', 'OUTSIDER'],
        'attendance.Billing' => ['NHIS', 'CASH AND CARRY', 'PRIVATE INSURANCE', 'PRIVATE INSURANCE + NHIS', 'STAFF/PROTOCOL'],
        'attendance.Status' => ['DOCTOR', 'DISCHARGING', 'VITALS', 'DONE', 'DISCHARGE', 'PHARMACY', 'LAB', 'INPATIENT-DISCH', 'ADMISSION', 'SCAN', 'ADMISSION-DETENTION', 'ADMISSION-ADMISSION', 'WARD'],
        'attendance.Triage' => ['NORMAL', 'MANAGEABLE', 'EMERGENCY'],
        'attendance.NewOld' => ['NEW', 'OLD'],
        'patients.Sex' => ['FEMALE', 'MALE', 'F', 'M', 'EMALE', 'FEMLE', 'MAL'],
        'patients.BillStatus' => ['NHIS', 'PRIVATE INSURANCE', 'CASH AND CARRY', 'PRIVATE INSURANCE + NHIS', 'PRIVATE INSURANCE +'],
        'billing.Status' => ['PAID', 'OWING'],
        'claims.ClaimStatus' => ['ACTIVE', 'ARCHIVES'],
        'claims.TypeBill' => ['ALL INCLUSIVE'],
        'claims.TypeServ' => ['OUTPATIENT', 'INPATIENT'],
        'consult_prescriptions.Status' => ['DONE', 'REQUESTED'],
        'consult_prescriptions.BillingStatus' => ['BILLED', 'OWING'],
        'consult_scan_lab.Status' => ['DONE', 'REQUESTED', 'SERVED'],
        'insurance.InsType' => ['PRIVATE INSURANCE', 'NHIS'],
        'medicine.MedType' => ['DRUG', 'CONSUMABLE'],
        'medicine.Category' => [],
        'medicine.Serve' => [],
        'services.ResultType' => [],
        'serv_criterias.ResultType' => [],
        'serv_results.Uploaded' => ['0'],
        'serv_results.ResFlag' => ['1', '.', 'C', 'T'],
        'users.Status' => ['ACTIVE', 'INACTIVE'],
        'users.CanAdd' => ['Y'],
        'users.CanEdit' => ['Y'],
        'users.CanDelete' => ['Y'],
        'users.CanPrint' => ['Y'],
        'beds.BedStatus' => ['AVAILABLE', 'OCCUPIED'],
        'batch.BatchStatus' => ['DONE'],
        'notifications.ReqStatus' => ['UNCHECKED'],
    ];

    public function __construct(
        private readonly DatabaseManager $databases,
        private readonly Filesystem $files,
    ) {}

    /** @return array<string, mixed> */
    public function capture(string $connectionName, string $expectedDatabase, string $outputDirectory, ?SourceAccountVerification $accountVerification = null): array
    {
        if ($connectionName !== self::APPROVED_CONNECTION) {
            throw new RuntimeException('Classic evidence capture refuses every connection except [legacy_uhms].');
        }
        if ($expectedDatabase !== self::APPROVED_DATABASE) {
            throw new RuntimeException('Classic evidence capture refuses every schema except the approved [uuhms].');
        }
        if ($accountVerification === null
            || ! in_array('SELECT', $accountVerification->privileges, true)
            || ! in_array('table', $accountVerification->inspectedSurfaces, true)
            || ! in_array('routine', $accountVerification->inspectedSurfaces, true)) {
            throw new RuntimeException('Classic evidence capture requires the authoritative complete privilege verification reference.');
        }

        $outputPath = $this->safeOutputPath($outputDirectory);
        $connection = $this->databases->connection($connectionName);
        $configuredDatabase = (string) $connection->getDatabaseName();

        if (! hash_equals(self::APPROVED_DATABASE, $configuredDatabase)) {
            throw new RuntimeException("Classic connection selected [{$configuredDatabase}], not approved [uuhms].");
        }

        $executedAt = gmdate('c');
        $toolCodeHash = $this->toolCodeHash();
        $query = new ReadOnlyQueryRecorder($connection, self::TOOL_VERSION);
        $server = $query->select(
            'classic.server_configuration',
            'SELECT DATABASE() AS database_name, VERSION() AS database_version, @@version_comment AS version_comment, @@character_set_database AS character_set_database, @@collation_database AS collation_database, @@global.read_only AS global_read_only, @@session.tx_read_only AS session_transaction_read_only, @@session.tx_isolation AS transaction_isolation, @@global.log_bin AS binary_logging_enabled, @@global.gtid_binlog_pos AS source_coordinate',
            purpose: 'Validate approved schema and capture non-secret server metadata.',
        )[0] ?? throw new RuntimeException('Unable to read Classic server metadata.');

        if ((string) $server->database_name !== self::APPROVED_DATABASE) {
            throw new RuntimeException('SELECT DATABASE() did not return the approved Classic schema.');
        }

        $tables = $this->rows($query->select(
            'classic.tables',
            'SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION, CREATE_OPTIONS FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [self::APPROVED_DATABASE],
            'Complete Classic table/view catalogue.',
        ));
        $columns = $this->rows($query->select(
            'classic.columns',
            'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, DATETIME_PRECISION, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [self::APPROVED_DATABASE],
            'Complete Classic 55-table column catalogue.',
        ));
        $indexes = $this->rows($query->select(
            'classic.indexes',
            'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, COLLATION, CARDINALITY, SUB_PART, NULLABLE, INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
            [self::APPROVED_DATABASE],
            'Complete Classic index catalogue.',
        ));
        $declaredConstraints = $this->rows($query->select(
            'classic.declared_constraints',
            'SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? ORDER BY TABLE_NAME, CONSTRAINT_NAME',
            [self::APPROVED_DATABASE],
            'Declared Classic primary/unique/foreign-key constraints.',
        ));
        $foreignKeyColumns = $this->rows($query->select(
            'classic.foreign_key_columns',
            'SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION',
            [self::APPROVED_DATABASE],
            'Independent foreign-key evidence from referenced columns; an empty result establishes no declared foreign-key columns visible to this account.',
        ));
        $referentialConstraints = $this->rows($query->select(
            'classic.referential_constraints',
            'SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, UPDATE_RULE, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? ORDER BY TABLE_NAME, CONSTRAINT_NAME',
            [self::APPROVED_DATABASE],
            'Independent foreign-key evidence from the referential-constraint registry.',
        ));
        $primaryKeyIndexes = array_values(array_unique(array_map(
            fn (array $row): string => (string) $row['TABLE_NAME'],
            array_filter($indexes, fn (array $row): bool => $row['INDEX_NAME'] === 'PRIMARY'),
        )));
        $nonPrimaryUniqueIndexes = array_values(array_unique(array_map(
            fn (array $row): string => $row['TABLE_NAME'].'|'.$row['INDEX_NAME'],
            array_filter($indexes, fn (array $row): bool => (int) $row['NON_UNIQUE'] === 0 && $row['INDEX_NAME'] !== 'PRIMARY'),
        )));

        $tableNames = array_map(fn (array $row): string => (string) $row['TABLE_NAME'], $tables);
        if (count($tableNames) !== 55 || count($columns) !== 479) {
            throw new RuntimeException('Classic schema fingerprint guard failed: expected 55 tables and 479 columns.');
        }

        $rowCounts = [];
        foreach ($tableNames as $table) {
            $this->assertIdentifier($table);
            $row = $query->select(
                'classic.row_count.'.$table,
                'SELECT COUNT(*) AS exact_count FROM '.$this->qualified($table),
                purpose: "Exact row count for {$table}.",
            )[0];
            $rowCounts[$table] = (int) $row->exact_count;
        }

        $statusResults = $this->statusResults($query);
        $duplicateResults = $this->duplicateResults($query);
        $dateResults = $this->dateResults($query);
        $temporalProfiles = $this->temporalProfiles($query, $columns);
        $criticalFieldResults = $this->criticalFieldResults($query);
        $relationships = $this->relationshipResults($query);
        $financialResults = $this->financialResults($query);
        $stockResults = $this->stockResults($query);
        $incremental = $this->incrementalCapabilities($tableNames, $columns, $indexes);

        $schemaManifest = [
            'manifest_version' => 2,
            'tool_version' => self::TOOL_VERSION,
            'tool_code_hash_sha256' => $toolCodeHash,
            'captured_at_utc' => $executedAt,
            'approved_database' => self::APPROVED_DATABASE,
            'database' => [
                'version' => (string) $server->database_version,
                'version_comment' => (string) $server->version_comment,
                'character_set' => (string) $server->character_set_database,
                'collation' => (string) $server->collation_database,
                'global_read_only' => (bool) $server->global_read_only,
                'session_transaction_read_only' => (bool) $server->session_transaction_read_only,
                'transaction_isolation' => (string) $server->transaction_isolation,
                'connection_transaction_level_observed' => $connection->transactionLevel(),
                'source_coordinate' => (string) $server->source_coordinate,
                'source_coordinate_status' => (bool) $server->binary_logging_enabled && (string) $server->source_coordinate !== '' ? 'available' : 'unavailable; binary logging disabled or GTID position empty',
                'binary_logging_enabled' => (bool) $server->binary_logging_enabled,
                'snapshot_semantics' => 'The command establishes REPEATABLE READ and a session-level READ ONLY transaction before capture. InnoDB consistent reads share the transaction snapshot established by the first read; information_schema metadata is server metadata and no reusable binlog/GTID coordinate is available.',
                'current_account_privileges' => $accountVerification->privileges,
                'privilege_verification_surfaces' => $accountVerification->inspectedSurfaces,
                'privilege_record_count' => $accountVerification->privilegeRecordCount,
                'account_identity' => 'redacted',
                'credentials_included' => false,
            ],
            'tables' => array_map(function (array $table) use ($rowCounts): array {
                $table['EXACT_ROW_COUNT'] = $rowCounts[$table['TABLE_NAME']];

                return $table;
            }, $tables),
            'columns' => $columns,
            'indexes' => $indexes,
            'declared_constraints' => $declaredConstraints,
            'constraint_evidence' => [
                'table_constraints_rows' => count($declaredConstraints),
                'table_constraints_visibility_note' => count($declaredConstraints) === 0
                    ? 'TABLE_CONSTRAINTS returned no rows even though primary indexes are visible in STATISTICS; it is not used alone to infer constraint absence.'
                    : null,
                'foreign_key_columns' => $foreignKeyColumns,
                'referential_constraints' => $referentialConstraints,
                'verified_foreign_key_count' => count(array_unique(array_map(
                    fn (array $row): string => $row['TABLE_NAME'].'|'.$row['CONSTRAINT_NAME'],
                    $foreignKeyColumns,
                ))),
                'primary_key_evidence_source' => 'indexes entries whose INDEX_NAME is PRIMARY',
                'primary_key_index_count' => count($primaryKeyIndexes),
                'non_primary_unique_index_count' => count($nonPrimaryUniqueIndexes),
                'index_constraint_reconciliation' => 'Primary and unique structure is derived from information_schema.STATISTICS because TABLE_CONSTRAINTS returned no visible rows; foreign-key absence is independently corroborated by both KEY_COLUMN_USAGE referenced columns and REFERENTIAL_CONSTRAINTS.',
            ],
        ];

        $aggregateResults = [
            'manifest_version' => 2,
            'tool_version' => self::TOOL_VERSION,
            'tool_code_hash_sha256' => $toolCodeHash,
            'captured_at_utc' => $executedAt,
            'approved_database' => self::APPROVED_DATABASE,
            'privacy' => [
                'record_level_values_included' => false,
                'patient_identifiers_included' => false,
                'free_text_included' => false,
                'patient_derived_temporal_extrema_included' => false,
                'temporal_extrema_repository_policy' => 'valid_min and valid_max are null in source control; temporal evidence queries return counts and quality buckets only',
                'categorical_values_restricted_to_safe_allowlist' => true,
                'unapproved_categorical_literals' => 'collapsed inside the database; only combined row count and distinct-value count are returned',
            ],
            'repository_sanitization' => [
                'policy_version' => 'phase-2c-temporal-extrema-redaction/1.0.0',
                'scope' => 'temporal_column_profiles.*.results.valid_min and valid_max',
                'redacted_field_count' => count($temporalProfiles) * 2,
                'replacement' => null,
                'raw_query_result_hashes_retained' => false,
                'note' => 'Counts and quality buckets are captured; exact patient/event extrema are not selected or source-controlled.',
            ],
            'row_counts' => $rowCounts,
            'total_rows' => array_sum($rowCounts),
            'categorical_status_counts' => $statusResults,
            'duplicate_identity_aggregates' => $duplicateResults,
            'date_quality_aggregates' => $dateResults,
            'temporal_column_profiles' => $temporalProfiles,
            'critical_field_quality_aggregates' => $criticalFieldResults,
            'financial_reconciliation' => $financialResults,
            'stock_snapshot_and_batch_reconciliation' => $stockResults,
        ];

        $relationshipManifest = [
            'manifest_version' => 2,
            'tool_version' => self::TOOL_VERSION,
            'tool_code_hash_sha256' => $toolCodeHash,
            'captured_at_utc' => $executedAt,
            'approved_database' => self::APPROVED_DATABASE,
            'declared_foreign_key_count' => count(array_unique(array_map(
                fn (array $row): string => $row['TABLE_NAME'].'|'.$row['CONSTRAINT_NAME'],
                $foreignKeyColumns,
            ))),
            'declared_foreign_key_evidence' => [
                'key_column_usage_rows' => count($foreignKeyColumns),
                'referential_constraints_rows' => count($referentialConstraints),
                'query_ids' => ['classic.foreign_key_columns', 'classic.referential_constraints'],
            ],
            'interpretation' => 'All registry relationships are inferred. Candidate sentinel rules are evidence labels, not approved mappings.',
            'relationships' => $relationships,
        ];

        $structuralTables = array_map(function (array $table): array {
            unset($table['EXACT_ROW_COUNT']);

            return $table;
        }, $schemaManifest['tables']);
        $structuralIndexes = array_map(function (array $index): array {
            unset($index['CARDINALITY']);

            return $index;
        }, $indexes);
        $fingerprintInput = [
            'approved_database' => self::APPROVED_DATABASE,
            'tables' => $structuralTables,
            'columns' => $columns,
            'indexes_without_cardinality' => $structuralIndexes,
            'declared_constraints' => $declaredConstraints,
            'foreign_key_columns' => $foreignKeyColumns,
            'referential_constraints' => $referentialConstraints,
        ];
        $fingerprint = hash('sha256', $this->json($fingerprintInput, false));
        $relationshipManifest['source_schema_fingerprint'] = $fingerprint;
        $snapshotFingerprint = hash('sha256', $this->json([
            'schema_fingerprint' => $fingerprint,
            'row_counts' => $rowCounts,
            'aggregate_results' => $aggregateResults,
            'relationships' => $relationships,
        ], false));
        $fingerprintManifest = [
            'manifest_version' => 2,
            'tool_version' => self::TOOL_VERSION,
            'tool_code_hash_sha256' => $toolCodeHash,
            'captured_at_utc' => $executedAt,
            'approved_database' => self::APPROVED_DATABASE,
            'algorithm' => 'sha256',
            'fingerprint' => $fingerprint,
            'fingerprint_kind' => 'structural_schema',
            'snapshot_fingerprint' => $snapshotFingerprint,
            'canonical_components' => ['approved_database', 'tables_without_row_counts', 'columns', 'indexes_without_cardinality', 'declared_constraints', 'foreign_key_columns', 'referential_constraints'],
            'canonicalization' => 'Arrays retain explicitly sorted query order; associative keys retain manifest construction order; UTF-8 JSON uses unescaped Unicode/slashes and no insignificant whitespace. Integer, decimal-string, boolean and null types are preserved.',
            'excluded_from_structural_fingerprint' => ['database_version', 'exact_row_counts', 'index_cardinality', 'capture_times', 'storage_sizes', 'auto_increment_next_values'],
            'table_count' => count($tables),
            'column_count' => count($columns),
            'total_rows' => array_sum($rowCounts),
        ];

        $finishedAt = gmdate('c');
        $this->files->ensureDirectoryExists($outputPath);
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'CLASSIC_SCHEMA_MANIFEST.json', $this->json($schemaManifest));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'CLASSIC_RELATIONSHIP_MANIFEST.json', $this->json($relationshipManifest));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'CLASSIC_AGGREGATE_RESULTS.json', $this->json($aggregateResults));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'CLASSIC_SCHEMA_FINGERPRINT.json', $this->json($fingerprintManifest));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'CLASSIC_INCREMENTAL_CAPABILITY_MATRIX.md', $this->incrementalMarkdown($incremental, $executedAt));
        $this->files->replace($outputPath.DIRECTORY_SEPARATOR.'CLASSIC_QUERY_MANIFEST.json', $this->json($query->manifest($executedAt, $finishedAt, self::APPROVED_DATABASE, [
            'tool_code_hash_sha256' => $toolCodeHash,
            'source_coordinate' => (string) $server->source_coordinate,
            'transaction_control' => [
                'session_read_only_observed' => (bool) $server->session_transaction_read_only,
                'transaction_isolation_observed' => (string) $server->transaction_isolation,
                'connection_transaction_level_observed' => $connection->transactionLevel(),
                'snapshot_semantics' => 'REPEATABLE READ, session READ ONLY; InnoDB snapshot begins on the first consistent read. No reusable source coordinate is available.',
            ],
            'temporal_result_hash_policy' => 'Temporal profile queries contain counts and quality buckets only; exact extrema are never selected, hashed or written.',
            'repository_sanitization' => [
                'policy_version' => 'phase-2c-temporal-result-hash-redaction/1.0.0',
                'query_selector' => 'classic.temporal_profile.* generated by phase-1b-classic-evidence/2.2.0 or later',
                'redacted_result_hash_count' => 0,
                'replacement' => null,
                'query_hashes_retained' => true,
                'note' => 'No result hash redaction is needed because temporal queries no longer select exact extrema.',
            ],
        ])));

        return [
            'database' => self::APPROVED_DATABASE,
            'table_count' => count($tables),
            'column_count' => count($columns),
            'total_rows' => array_sum($rowCounts),
            'fingerprint' => $fingerprint,
        ];
    }

    /** @return array<string, array<int, array<string,mixed>>> */
    private function statusResults(ReadOnlyQueryRecorder $query): array
    {
        $fields = [
            ['attendance', 'AttStatus'], ['attendance', 'Billing'], ['attendance', 'Status'], ['attendance', 'Triage'], ['attendance', 'NewOld'],
            ['patients', 'Sex'], ['patients', 'BillStatus'], ['billing', 'Status'], ['claims', 'ClaimStatus'], ['claims', 'TypeBill'], ['claims', 'TypeServ'],
            ['consult_prescriptions', 'Status'], ['consult_prescriptions', 'BillingStatus'], ['consult_scan_lab', 'Status'], ['insurance', 'InsType'],
            ['medicine', 'MedType'], ['medicine', 'Category'], ['medicine', 'Serve'], ['services', 'ResultType'], ['serv_criterias', 'ResultType'],
            ['serv_results', 'Uploaded'], ['serv_results', 'ResFlag'], ['users', 'Status'], ['users', 'CanAdd'], ['users', 'CanEdit'], ['users', 'CanDelete'], ['users', 'CanPrint'],
            ['beds', 'BedStatus'], ['batch', 'BatchStatus'], ['notifications', 'ReqStatus'],
        ];
        $results = [];
        foreach ($fields as [$table, $column]) {
            $this->assertIdentifier($table);
            $this->assertIdentifier($column);
            $approved = self::SAFE_CATEGORY_LITERALS[$table.'.'.$column] ?? [];
            $approvedCondition = $approved === []
                ? '0 = 1'
                : 'normalized_value IN ('.implode(', ', array_fill(0, count($approved), '?')).')';
            $rows = $query->select(
                'classic.category.'.$table.'.'.$column,
                'SELECT CASE WHEN normalized_value = \'__NULL__\' OR '.$approvedCondition.' THEN normalized_value ELSE \'__REDACTED_UNAPPROVED_LITERAL__\' END AS category_value, COUNT(*) AS aggregate_count, COUNT(DISTINCT normalized_value) AS distinct_value_count FROM (SELECT COALESCE(NULLIF(TRIM(CAST('.$this->quote($column).' AS CHAR)), CHAR(0)), \'__NULL__\') AS normalized_value FROM '.$this->qualified($table).') AS sanitized_values GROUP BY category_value ORDER BY aggregate_count DESC, category_value',
                $approved,
                "Safe low-cardinality aggregate for {$table}.{$column}; unapproved literals are collapsed inside the database before results are returned.",
            );
            $field = $table.'.'.$column;
            $results[$field] = array_map(fn (object $row): array => [
                'value' => (string) $row->category_value,
                'count' => (int) $row->aggregate_count,
                'distinct_value_count' => (int) $row->distinct_value_count,
                'redacted' => (string) $row->category_value === '__REDACTED_UNAPPROVED_LITERAL__',
            ], $rows);
        }

        return $results;
    }

    /** @return array<string, array<string,mixed>> */
    private function duplicateResults(ReadOnlyQueryRecorder $query): array
    {
        $definitions = [
            'patients.opd_number' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`patients` WHERE TRIM(`OpdNo`) <> \'\' GROUP BY UPPER(TRIM(`OpdNo`)) HAVING COUNT(*) > 1) duplicate_groups',
            'patients.normalized_name' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`patients` WHERE TRIM(`PatientName`) <> \'\' GROUP BY UPPER(TRIM(`PatientName`)) HAVING COUNT(*) > 1) duplicate_groups',
            'insurance.member_number' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`insurance` WHERE TRIM(`MemberNo`) <> \'\' GROUP BY UPPER(TRIM(`MemberNo`)) HAVING COUNT(*) > 1) duplicate_groups',
            'insurance.patient_provider_tuple' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`insurance` GROUP BY `PAT_ID`, UPPER(TRIM(`InsType`)), UPPER(TRIM(`Company`)), UPPER(TRIM(`Scheme`)) HAVING COUNT(*) > 1) duplicate_groups',
            'users.normalized_username' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`users` WHERE TRIM(`UserName`) <> \'\' GROUP BY UPPER(TRIM(`UserName`)) HAVING COUNT(*) > 1) duplicate_groups',
            'medicine.code' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`medicine` WHERE TRIM(`MedCode`) <> \'\' GROUP BY UPPER(TRIM(`MedCode`)) HAVING COUNT(*) > 1) duplicate_groups',
            'services.normalized_name' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`services` WHERE TRIM(`Service`) <> \'\' GROUP BY UPPER(TRIM(`Service`)) HAVING COUNT(*) > 1) duplicate_groups',
            'batch.number' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`batch` WHERE TRIM(`BatchNo`) <> \'\' GROUP BY UPPER(TRIM(`BatchNo`)) HAVING COUNT(*) > 1) duplicate_groups',
            'serv_options_cri.id' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`serv_options_cri` GROUP BY `OPTC_ID` HAVING COUNT(*) > 1) duplicate_groups',
            'serv_options_out.id' => 'SELECT COUNT(*) AS duplicate_group_count, COALESCE(SUM(group_rows), 0) AS duplicate_row_count FROM (SELECT COUNT(*) AS group_rows FROM `uuhms`.`serv_options_out` GROUP BY `OPTO_ID` HAVING COUNT(*) > 1) duplicate_groups',
        ];
        $results = [];
        foreach ($definitions as $id => $sql) {
            $row = $query->select('classic.duplicates.'.$id, $sql, purpose: 'Duplicate aggregate only; grouped values are not returned.')[0];
            $results[$id] = ['duplicate_group_count' => (int) $row->duplicate_group_count, 'duplicate_row_count' => (int) $row->duplicate_row_count];
        }

        return $results;
    }

    /** @return array<string, array<string,mixed>> */
    private function dateResults(ReadOnlyQueryRecorder $query): array
    {
        $definitions = [
            'patients.dob' => "SELECT COUNT(*) AS total_rows, SUM(CAST(`DOB` AS CHAR) = '0000-00-00') AS zero_date_count, SUM(`DOB` < '1900-01-01' AND CAST(`DOB` AS CHAR) <> '0000-00-00') AS pre_1900_count, SUM(`DOB` > UTC_DATE()) AS future_count, SUM(`DOB` > DATE(`RegDate`)) AS dob_after_registration_count FROM `uuhms`.`patients`",
            'insurance.coverage_dates' => "SELECT COUNT(*) AS total_rows, SUM(CAST(`IssueDate` AS CHAR) = '0000-00-00') AS zero_issue_count, SUM(CAST(`ExpiryDate` AS CHAR) = '0000-00-00') AS zero_expiry_count, SUM(`IssueDate` > UTC_DATE()) AS future_issue_count, SUM(`ExpiryDate` > UTC_DATE()) AS future_expiry_count, SUM(CAST(`IssueDate` AS CHAR) <> '0000-00-00' AND CAST(`ExpiryDate` AS CHAR) <> '0000-00-00' AND `ExpiryDate` < `IssueDate`) AS expiry_before_issue_count FROM `uuhms`.`insurance`",
            'attendance.admission_dates' => 'SELECT COUNT(*) AS total_rows, SUM(`DischDate` < `AdmitDate`) AS discharge_before_admission_count, SUM(`DischDate` = `AdmitDate`) AS same_admit_discharge_count, SUM(`AdmitDate` = DATE(`AttDate`)) AS admission_equals_attendance_count, SUM(`DischDate` = DATE(`AttDate`)) AS discharge_equals_attendance_count FROM `uuhms`.`attendance`',
            'claims.admission_dates' => 'SELECT COUNT(*) AS total_rows, SUM(`DischClaim` < `AdmitClaim`) AS discharge_before_admission_count, SUM(`DischClaim` > UTC_DATE()) AS future_discharge_count FROM `uuhms`.`claims`',
            'claims_prescriptions.prescription_date' => "SELECT COUNT(*) AS total_rows, SUM(CAST(`PresDate` AS CHAR) LIKE '0000-00-00%') AS zero_date_count FROM `uuhms`.`claims_prescriptions`",
            'appointement.app_date' => "SELECT COUNT(*) AS total_rows, SUM(`AppDate` IS NULL) AS null_count, SUM(TIME(`AppDate`) = '00:00:00') AS midnight_count, SUM(`AppDate` > UTC_TIMESTAMP()) AS future_count, SUM(`AppDate` >= '2100-01-01') AS year_2100_or_later_count FROM `uuhms`.`appointement`",
            'batch.expiry' => 'SELECT COUNT(*) AS total_rows, SUM(`ExpiryDate` > UTC_DATE()) AS future_expiry_count, SUM(`ExpiryDate` < UTC_DATE() AND `Expired` = 0) AS past_but_not_flagged_count FROM `uuhms`.`batch`',
            'request.timeline' => 'SELECT COUNT(*) AS total_rows, SUM(`SuppDate` < `ReqDate`) AS supplied_before_request_count FROM `uuhms`.`request`',
        ];
        $results = [];
        foreach ($definitions as $id => $sql) {
            $results[$id] = $this->numericRow($query->select('classic.date_quality.'.$id, $sql, purpose: 'Date-quality aggregate without record identifiers.')[0]);
        }

        return $results;
    }

    /** @param array<int, array<string, mixed>> $columns @return array<string, array<string, mixed>> */
    private function temporalProfiles(ReadOnlyQueryRecorder $query, array $columns): array
    {
        $results = [];
        foreach ($columns as $column) {
            if (! in_array(strtolower((string) $column['DATA_TYPE']), ['date', 'datetime', 'timestamp'], true)) {
                continue;
            }

            $table = (string) $column['TABLE_NAME'];
            $field = (string) $column['COLUMN_NAME'];
            $this->assertIdentifier($table);
            $this->assertIdentifier($field);
            $ref = $this->quote($field);
            $valid = "CAST({$ref} AS CHAR) NOT LIKE '0000-00-00%'";
            $sql = 'SELECT COUNT(*) AS total_rows, '
                ."SUM({$ref} IS NULL) AS null_count, "
                ."SUM(CAST({$ref} AS CHAR) LIKE '0000-00-00%') AS zero_date_count, "
                ."SUM({$valid} AND {$ref} < '1900-01-01') AS pre_1900_count, "
                ."SUM({$valid} AND {$ref} > UTC_TIMESTAMP()) AS future_count "
                .'FROM '.$this->qualified($table);
            $id = $table.'.'.$field;
            $row = $this->numericRow($query->select('classic.temporal_profile.'.$id, $sql, purpose: 'Aggregate temporal quality profile; counts and quality buckets only, with exact extrema prohibited.')[0]);
            $results[$id] = [
                'data_type' => $column['DATA_TYPE'],
                'on_update' => str_contains(strtolower((string) $column['EXTRA']), 'on update'),
                'watermark_semantics' => str_contains(strtolower((string) $column['EXTRA']), 'on update')
                    ? 'mutable schema-managed timestamp candidate; business meaning and delete coverage require approval'
                    : 'business/event date only; not evidence of row-change chronology',
                'results' => [
                    'total_rows' => $row['total_rows'],
                    'null_count' => $row['null_count'],
                    'zero_date_count' => $row['zero_date_count'],
                    'valid_min' => null,
                    'valid_max' => null,
                    'pre_1900_count' => $row['pre_1900_count'],
                    'future_count' => $row['future_count'],
                ],
            ];
        }

        return $results;
    }

    /** @return array<string, array<string, mixed>> */
    private function criticalFieldResults(ReadOnlyQueryRecorder $query): array
    {
        $definitions = [
            'patients.required_and_identity_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`PatientName`), '') = '') AS empty_name_count, SUM(COALESCE(TRIM(`OpdNo`), '') = '') AS empty_opd_count, SUM(COALESCE(TRIM(`Sex`), '') = '') AS empty_sex_count, SUM(COALESCE(TRIM(`PhoneNo`), '') = '') AS empty_phone_count, SUM(COALESCE(TRIM(`OriginalName`), '') = '') AS empty_original_name_count, SUM(COALESCE(TRIM(`OriginalOpd`), '') = '') AS empty_original_opd_count FROM `uuhms`.`patients`",
            'insurance.identity_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`MemberNo`), '') = '') AS empty_member_number_count, SUM(COALESCE(TRIM(`Company`), '') = '') AS empty_company_count, SUM(COALESCE(TRIM(`Scheme`), '') = '') AS empty_scheme_count FROM `uuhms`.`insurance`",
            'medicine.catalogue_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`MedName`), '') = '') AS empty_name_count, SUM(COALESCE(TRIM(`MedCode`), '') = '') AS empty_code_count, SUM(COALESCE(TRIM(`Category`), '') = '') AS empty_category_count, SUM(COALESCE(TRIM(`Serve`), '') = '') AS empty_serve_count FROM `uuhms`.`medicine`",
            'services.catalogue_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`Service`), '') = '') AS empty_name_count, SUM(COALESCE(TRIM(`Departement`), '') = '') AS empty_department_count, SUM(COALESCE(TRIM(`ResultType`), '') = '') AS empty_result_type_count FROM `uuhms`.`services`",
            'consult_history.content_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`HistoryOfComplain`), '') = '') AS empty_plain_count, SUM(COALESCE(TRIM(`RtfHistoryOfComplaints`), '') = '') AS empty_rtf_count FROM `uuhms`.`consult_history`",
            'consult_treatment.content_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`TreatmentPlan`), '') = '') AS empty_plain_count, SUM(COALESCE(TRIM(`RtfTreatmentPlan`), '') = '') AS empty_rtf_count FROM `uuhms`.`consult_treatment`",
            'consult_scan_lab.result_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`Result`), '') = '') AS empty_result_count, SUM(COALESCE(TRIM(`Interpretation`), '') = '') AS empty_interpretation_count, SUM(COALESCE(TRIM(`Description`), '') = '') AS empty_description_count FROM `uuhms`.`consult_scan_lab`",
            'serv_results.result_fields' => "SELECT COUNT(*) AS total_rows, SUM(COALESCE(TRIM(`Result`), '') = '') AS empty_result_count, SUM(COALESCE(TRIM(`ResultOutcome`), '') = '') AS empty_outcome_count FROM `uuhms`.`serv_results`",
        ];

        $results = [];
        foreach ($definitions as $id => $sql) {
            $results[$id] = $this->numericRow($query->select('classic.critical_fields.'.$id, $sql, purpose: 'Critical-field blank counts only; no field values or row identifiers returned.')[0]);
        }

        return $results;
    }

    /** @return array<int, array<string,mixed>> */
    private function relationshipResults(ReadOnlyQueryRecorder $query): array
    {
        $definitions = $this->relationshipDefinitions();
        $results = [];
        foreach ($definitions as $definition) {
            foreach (['child_table', 'child_column', 'parent_table', 'parent_column'] as $key) {
                $this->assertIdentifier($definition[$key]);
            }
            $childRef = 'c.'.$this->quote($definition['child_column']);
            $parentRef = 'p.'.$this->quote($definition['parent_column']);
            $join = $definition['join_sql'] ?? "{$childRef} = {$parentRef}";
            $sentinel = $definition['sentinel_sql'] ?? "{$childRef} = 0";
            if (($definition['relationship_match_mode'] ?? 'left_join') === 'exists') {
                $exists = 'EXISTS (SELECT 1 FROM '.$this->qualified($definition['parent_table'])." p WHERE {$join})";
                $sql = 'SELECT COUNT(*) AS child_rows, '
                    ."SUM({$childRef} IS NULL) AS null_count, "
                    ."SUM({$childRef} IS NOT NULL AND ({$sentinel})) AS candidate_sentinel_count, "
                    ."SUM({$childRef} IS NOT NULL AND NOT ({$sentinel}) AND {$exists}) AS matched_non_sentinel_count, "
                    ."SUM({$childRef} IS NOT NULL AND NOT ({$sentinel}) AND NOT {$exists}) AS orphan_excluding_candidate_sentinel_count, "
                    ."SUM({$childRef} IS NOT NULL AND NOT {$exists}) AS unmatched_including_candidate_sentinel_count "
                    .'FROM '.$this->qualified($definition['child_table']).' c';
            } else {
                $sql = 'SELECT COUNT(*) AS child_rows, '
                    ."SUM({$childRef} IS NULL) AS null_count, "
                    ."SUM({$childRef} IS NOT NULL AND ({$sentinel})) AS candidate_sentinel_count, "
                    ."SUM({$childRef} IS NOT NULL AND NOT ({$sentinel}) AND {$parentRef} IS NOT NULL) AS matched_non_sentinel_count, "
                    ."SUM({$childRef} IS NOT NULL AND NOT ({$sentinel}) AND {$parentRef} IS NULL) AS orphan_excluding_candidate_sentinel_count, "
                    ."SUM({$childRef} IS NOT NULL AND {$parentRef} IS NULL) AS unmatched_including_candidate_sentinel_count "
                    .'FROM '.$this->qualified($definition['child_table']).' c LEFT JOIN '.$this->qualified($definition['parent_table'])." p ON {$join}";
            }
            $queryId = 'classic.relationship.'.$definition['id'];
            $row = $query->select($queryId, $sql, purpose: 'Aggregate inferred relationship check; no child or parent values returned.')[0];
            $results[] = array_merge($definition, [
                'join_predicate' => $this->normalizeSql($join),
                'sentinel_assumption' => $definition['sentinel_assumption'] ?? 'Numeric zero is a candidate sentinel, not an approved mapping rule.',
                'counts' => $this->numericRow($row),
                'evidence_query' => $query->evidenceFor($queryId),
            ]);
        }

        return $results;
    }

    /** @return array<int, array<string,string>> */
    private function relationshipDefinitions(): array
    {
        $edges = [
            ['accounts.title', 'accounts', 'TITLE_ID', 'acc_titles', 'TI_ID'], ['accounts.bank', 'accounts', 'BANK_ACC_ID', 'bank', 'BANK_ACC_ID'],
            ['appointement.attendance', 'appointement', 'ATT_ID', 'attendance', 'ATT_ID'],
            ['attendance.patient', 'attendance', 'PAT_ID', 'patients', 'PAT_ID'], ['attendance.bed', 'attendance', 'BED_ID', 'beds', 'BED_ID'], ['attendance.user', 'attendance', 'USER_ID', 'users', 'USER_ID'],
            ['batch.medicine', 'batch', 'MED_ID', 'medicine', 'MED_ID'], ['batch.supplier', 'batch', 'SUP_ID', 'suppliers', 'SUP_ID'], ['batch.user', 'batch', 'USER_ID', 'users', 'USER_ID'],
            ['beds.patient', 'beds', 'PAT_ID', 'patients', 'PAT_ID'],
            ['billing.attendance', 'billing', 'ATT_ID', 'attendance', 'ATT_ID'], ['billing.department', 'billing', 'DEP_ID', 'departements', 'DEP_ID'], ['billing.service', 'billing', 'SERV_ID', 'services', 'SERV_ID'], ['billing.user', 'billing', 'USER_ID', 'users', 'USER_ID'],
            ['claims.attendance', 'claims', 'ATT_ID', 'attendance', 'ATT_ID'], ['claims.specialty', 'claims', 'SPEC_ID', 'claims_specialty', 'SPEC_ID'],
            ['claims_diagnosis.claim', 'claims_diagnosis', 'CLAIM_ID', 'claims', 'CLAIM_ID'], ['claims_diagnosis.diagnosis', 'claims_diagnosis', 'LDIAG_ID', 'list_diagnosis', 'LDIAG_ID'],
            ['claims_prescriptions.claim', 'claims_prescriptions', 'CLAIM_ID', 'claims', 'CLAIM_ID'], ['claims_prescriptions.medicine', 'claims_prescriptions', 'MED_ID', 'medicine', 'MED_ID'],
            ['claims_procedures.claim', 'claims_procedures', 'CLAIM_ID', 'claims', 'CLAIM_ID'], ['claims_procedures.procedure', 'claims_procedures', 'LPRO_ID', 'list_procedures', 'LPRO_ID'],
            ['claims_scan_lab.claim', 'claims_scan_lab', 'CLAIM_ID', 'claims', 'CLAIM_ID'], ['claims_scan_lab.procedure', 'claims_scan_lab', 'LPRO_ID', 'list_procedures', 'LPRO_ID'],
            ['consult_complaints.attendance', 'consult_complaints', 'ATT_ID', 'attendance', 'ATT_ID'], ['consult_complaints.catalogue', 'consult_complaints', 'LCOMP_ID', 'list_complaints', 'LCOMP_ID'],
            ['consult_diagnosis.attendance', 'consult_diagnosis', 'ATT_ID', 'attendance', 'ATT_ID'], ['consult_diagnosis.catalogue', 'consult_diagnosis', 'LDIAG_ID', 'list_diagnosis', 'LDIAG_ID'],
            ['consult_history.attendance', 'consult_history', 'ATT_ID', 'attendance', 'ATT_ID'],
            ['consult_prescriptions.attendance', 'consult_prescriptions', 'ATT_ID', 'attendance', 'ATT_ID'], ['consult_prescriptions.medicine', 'consult_prescriptions', 'MED_ID', 'medicine', 'MED_ID'], ['consult_prescriptions.billing', 'consult_prescriptions', 'BILL_ID', 'billing', 'BILL_ID'], ['consult_prescriptions.user', 'consult_prescriptions', 'USER_ID', 'users', 'USER_ID'], ['consult_prescriptions.department', 'consult_prescriptions', 'DEP_ID', 'departements', 'DEP_ID'],
            ['consult_procedures.attendance', 'consult_procedures', 'ATT_ID', 'attendance', 'ATT_ID'], ['consult_procedures.procedure', 'consult_procedures', 'LPRO_ID', 'list_procedures', 'LPRO_ID'],
            ['consult_scan_lab.attendance', 'consult_scan_lab', 'ATT_ID', 'attendance', 'ATT_ID'], ['consult_scan_lab.service', 'consult_scan_lab', 'SERV_ID', 'services', 'SERV_ID'], ['consult_scan_lab.user', 'consult_scan_lab', 'USER_ID', 'users', 'USER_ID'],
            ['consult_services.attendance', 'consult_services', 'ATT_ID', 'attendance', 'ATT_ID'], ['consult_services.service', 'consult_services', 'SERV_ID', 'services', 'SERV_ID'],
            ['consult_treatment.attendance', 'consult_treatment', 'ATT_ID', 'attendance', 'ATT_ID'],
            ['insurance.patient', 'insurance', 'PAT_ID', 'patients', 'PAT_ID'],
            ['list_causes.diagnosis', 'list_causes', 'LDIAG_ID', 'list_diagnosis', 'LDIAG_ID'], ['list_diagnosis.disorder', 'list_diagnosis', 'LDIS_ID', 'list_disorder', 'LDIS_ID'], ['list_signs.diagnosis', 'list_signs', 'LDIAG_ID', 'list_diagnosis', 'LDIAG_ID'],
            ['mat_family.patient', 'mat_family', 'PAT_ID', 'patients', 'PAT_ID'], ['mat_obstetrics.patient', 'mat_obstetrics', 'PAT_ID', 'patients', 'PAT_ID'],
            ['medicine.department', 'medicine', 'DEP_ID', 'departements', 'DEP_ID'], ['med_pharm.medicine', 'med_pharm', 'MED_ID', 'medicine', 'MED_ID'], ['med_store.medicine', 'med_store', 'MED_ID', 'medicine', 'MED_ID'],
            ['notifications.from_department', 'notifications', 'ReqFromDep', 'departements', 'DEP_ID'], ['notifications.to_department', 'notifications', 'ReqToDep', 'departements', 'DEP_ID'],
            ['nurses_note.attendance', 'nurses_note', 'ATT_ID', 'attendance', 'ATT_ID'], ['nurses_note.user', 'nurses_note', 'USER_ID', 'users', 'USER_ID'],
            ['request.medicine', 'request', 'MED_ID', 'medicine', 'MED_ID'], ['request.department', 'request', 'DEP_ID', 'departements', 'DEP_ID'], ['request.user', 'request', 'USER_ID', 'users', 'USER_ID'],
            ['serv_criterias.group', 'serv_criterias', 'GROUP_ID', 'serv_group', 'GROUP_ID'], ['serv_group.service', 'serv_group', 'SERV_ID', 'services', 'SERV_ID'], ['serv_options_cri.criteria', 'serv_options_cri', 'CRI_ID', 'serv_criterias', 'CRI_ID'], ['serv_options_out.service', 'serv_options_out', 'SERV_ID', 'services', 'SERV_ID'],
            ['serv_results.attendance', 'serv_results', 'ATT_ID', 'attendance', 'ATT_ID'], ['serv_results.criteria', 'serv_results', 'CRI_ID', 'serv_criterias', 'CRI_ID'], ['serv_results.service', 'serv_results', 'SERV_ID', 'services', 'SERV_ID'], ['serv_results.scan_lab', 'serv_results', 'SCL_ID', 'consult_scan_lab', 'SCL_ID'],
            ['serv_results.claim_scan_lab_alternative', 'serv_results', 'SCL_ID', 'claims_scan_lab', 'SCL_ID'],
            ['treatment.attendance', 'treatment', 'ATT_ID', 'attendance', 'ATT_ID'], ['treatment.medicine', 'treatment', 'MED_ID', 'medicine', 'MED_ID'], ['treatment.user', 'treatment', 'USER_ID', 'users', 'USER_ID'],
            ['vitals.attendance', 'vitals', 'ATT_ID', 'attendance', 'ATT_ID'], ['vitals.user', 'vitals', 'USER_ID', 'users', 'USER_ID'],
        ];

        $definitions = array_map(fn (array $edge): array => [
            'id' => $edge[0], 'child_table' => $edge[1], 'child_column' => $edge[2], 'parent_table' => $edge[3], 'parent_column' => $edge[4],
            'relationship_status' => 'inferred',
            'sentinel_assumption' => 'Numeric zero is treated as a candidate sentinel for reporting only; stakeholder approval remains required.',
        ], $edges);

        $naturalKeyDefinitions = [
            ['id' => 'list_symptoms.diagnosis', 'child_table' => 'list_symptoms', 'child_column' => 'LDIAG_ID', 'parent_table' => 'list_diagnosis', 'parent_column' => 'LDIAG_ID', 'join_sql' => 'TRIM(c.`LDIAG_ID`) = CAST(p.`LDIAG_ID` AS CHAR)', 'sentinel_sql' => "TRIM(c.`LDIAG_ID`) IN ('', '0')", 'sentinel_assumption' => 'Only blank or literal zero is a candidate sentinel; nonnumeric text remains an explicit unmatched value.'],
            ['id' => 'users.department', 'child_table' => 'users', 'child_column' => 'DEP_ID', 'parent_table' => 'departements', 'parent_column' => 'DEP_ID', 'join_sql' => 'TRIM(c.`DEP_ID`) = CAST(p.`DEP_ID` AS CHAR)', 'sentinel_sql' => "TRIM(c.`DEP_ID`) IN ('', '0')", 'sentinel_assumption' => 'Blank or literal zero is a candidate sentinel; the varchar-to-numeric join remains inferred.'],
            ['id' => 'services.department_name', 'child_table' => 'services', 'child_column' => 'Departement', 'parent_table' => 'departements', 'parent_column' => 'Departement', 'join_sql' => 'UPPER(TRIM(c.`Departement`)) = UPPER(TRIM(p.`Departement`))', 'sentinel_sql' => "TRIM(c.`Departement`) = ''", 'sentinel_assumption' => 'Blank is a candidate sentinel; normalized-name matching is inferred and is not an approved natural key.'],
            ['id' => 'gens.department_name', 'child_table' => 'gens', 'child_column' => 'Departement', 'parent_table' => 'departements', 'parent_column' => 'Departement', 'join_sql' => 'UPPER(TRIM(c.`Departement`)) = UPPER(TRIM(p.`Departement`))', 'sentinel_sql' => "TRIM(c.`Departement`) = ''", 'sentinel_assumption' => 'Blank is a candidate sentinel; normalized-name matching is inferred and is not an approved natural key.'],
            ['id' => 'insurance.provider_name_candidate', 'child_table' => 'insurance', 'child_column' => 'Company', 'parent_table' => 'sett_private', 'parent_column' => 'PrivateName', 'join_sql' => 'UPPER(TRIM(c.`Company`)) = UPPER(TRIM(p.`PrivateName`)) OR UPPER(TRIM(c.`Company`)) = UPPER(TRIM(p.`PrivateShort`))', 'sentinel_sql' => "TRIM(c.`Company`) = ''", 'sentinel_assumption' => 'Blank is a candidate sentinel; provider name/short-name matching is a tested alternative, not an approved natural key.'],
        ];

        foreach ($naturalKeyDefinitions as &$definition) {
            $definition['relationship_status'] = 'inferred';
            $definition['relationship_match_mode'] = 'exists';
        }
        unset($definition);

        return array_merge($definitions, $naturalKeyDefinitions);
    }

    /** @return array<string,array<string,mixed>> */
    private function financialResults(ReadOnlyQueryRecorder $query): array
    {
        $billing = $query->select(
            'classic.finance.billing_equation',
            'SELECT COUNT(*) AS total_rows, ROUND(SUM(CAST(`Bill` AS DECIMAL(20,4))), 2) AS bill_total, ROUND(SUM(CAST(`Discount` AS DECIMAL(20,4))), 2) AS discount_total, ROUND(SUM(CAST(`Paid` AS DECIMAL(20,4))), 2) AS paid_total, ROUND(SUM(CAST(`Balance` AS DECIMAL(20,4))), 2) AS balance_total, SUM(ABS((CAST(`Bill` AS DECIMAL(20,4)) - CAST(`Discount` AS DECIMAL(20,4)) - CAST(`Paid` AS DECIMAL(20,4))) - CAST(`Balance` AS DECIMAL(20,4))) > 0.01) AS mismatch_count, ROUND(SUM((CAST(`Bill` AS DECIMAL(20,4)) - CAST(`Discount` AS DECIMAL(20,4)) - CAST(`Paid` AS DECIMAL(20,4))) - CAST(`Balance` AS DECIMAL(20,4))), 2) AS aggregate_difference, SUM(`Bill` < 0 OR `Discount` < 0 OR `Paid` < 0 OR `Balance` < 0) AS negative_component_count, SUM(`Status` = \'PAID\' AND `Balance` > 0.01) AS paid_with_positive_balance_count, SUM(`Status` = \'OWING\' AND `Balance` <= 0.01) AS owing_with_non_positive_balance_count FROM `uuhms`.`billing`',
            purpose: 'Candidate Classic billing equation; evidence only, not an approved formula.',
        )[0];
        $claims = $query->select(
            'classic.finance.claim_equation',
            'SELECT COUNT(*) AS total_rows, ROUND(SUM(CAST(`ClaimTotal` AS DECIMAL(20,4))), 2) AS claim_total, ROUND(SUM(CAST(`ServTariff` AS DECIMAL(20,4))), 2) AS service_total, ROUND(SUM(CAST(`InvTariff` AS DECIMAL(20,4))), 2) AS investigation_total, ROUND(SUM(CAST(`PharmTariff` AS DECIMAL(20,4))), 2) AS pharmacy_total, SUM(ABS(CAST(`ClaimTotal` AS DECIMAL(20,4)) - (CAST(`ServTariff` AS DECIMAL(20,4)) + CAST(`InvTariff` AS DECIMAL(20,4)) + CAST(`PharmTariff` AS DECIMAL(20,4)))) > 0.01) AS mismatch_count, ROUND(SUM(CAST(`ClaimTotal` AS DECIMAL(20,4)) - (CAST(`ServTariff` AS DECIMAL(20,4)) + CAST(`InvTariff` AS DECIMAL(20,4)) + CAST(`PharmTariff` AS DECIMAL(20,4)))), 2) AS aggregate_difference, SUM(`ClaimTotal` < 0 OR `ServTariff` < 0 OR `InvTariff` < 0 OR `PharmTariff` < 0) AS negative_component_count FROM `uuhms`.`claims`',
            purpose: 'Candidate Classic claim equation; evidence only, not an approved formula.',
        )[0];

        return [
            'billing_candidate_equation' => ['equation' => 'Bill - Discount - Paid = Balance', 'approval_status' => 'stakeholder decision required', 'results' => $this->numericRow($billing)],
            'claim_candidate_equation' => ['equation' => 'ClaimTotal = ServTariff + InvTariff + PharmTariff', 'approval_status' => 'stakeholder decision required', 'results' => $this->numericRow($claims)],
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function stockResults(ReadOnlyQueryRecorder $query): array
    {
        $definitions = [
            'med_pharm.snapshot' => 'SELECT COUNT(*) AS total_rows, ROUND(SUM(CAST(`Qty` AS DECIMAL(20,4))), 4) AS quantity_total, SUM(`Qty` < 0 OR `Cash` < 0 OR `Nhis` < 0 OR `Private` < 0 OR `Treshold` < 0 OR `NhisClaim` < 0) AS negative_component_count, (SELECT COUNT(*) FROM (SELECT `MED_ID` FROM `uuhms`.`med_pharm` GROUP BY `MED_ID` HAVING COUNT(*) > 1) duplicate_products) AS duplicate_product_group_count FROM `uuhms`.`med_pharm`',
            'med_store.snapshot' => 'SELECT COUNT(*) AS total_rows, ROUND(SUM(CAST(`Qty` AS DECIMAL(20,4))), 4) AS quantity_total, SUM(`Qty` < 0 OR `Cash` < 0 OR `Nhis` < 0 OR `Private` < 0 OR `Treshold` < 0 OR `NhisClaim` < 0) AS negative_component_count, (SELECT COUNT(*) FROM (SELECT `MED_ID` FROM `uuhms`.`med_store` GROUP BY `MED_ID` HAVING COUNT(*) > 1) duplicate_products) AS duplicate_product_group_count FROM `uuhms`.`med_store`',
            'batch.snapshot' => 'SELECT COUNT(*) AS total_rows, ROUND(SUM(CAST(`Qty` AS DECIMAL(20,4))), 4) AS quantity_total, SUM(`Qty` < 0 OR `Buy` < 0) AS negative_component_count, SUM(`ExpiryDate` < UTC_DATE() AND `Expired` = 0) AS past_but_not_flagged_count FROM `uuhms`.`batch`',
            'request.snapshot' => 'SELECT COUNT(*) AS total_rows, SUM(`StoreQty` < 0 OR `PharmQty` < 0 OR `ReqQty` < 0 OR `SuppQty` < 0 OR `PharmNewQty` < 0) AS negative_quantity_row_count FROM `uuhms`.`request`',
            'pharmacy_vs_store.product_reconciliation' => 'SELECT COUNT(*) AS shared_product_count, SUM(p.qty = s.qty) AS equal_quantity_product_count, ROUND(SUM(p.qty - s.qty), 4) AS aggregate_quantity_difference FROM (SELECT `MED_ID`, SUM(CAST(`Qty` AS DECIMAL(20,4))) qty FROM `uuhms`.`med_pharm` GROUP BY `MED_ID`) p INNER JOIN (SELECT `MED_ID`, SUM(CAST(`Qty` AS DECIMAL(20,4))) qty FROM `uuhms`.`med_store` GROUP BY `MED_ID`) s ON s.`MED_ID` = p.`MED_ID`',
            'pharmacy_vs_store.unmatched_products' => 'SELECT (SELECT COUNT(*) FROM (SELECT `MED_ID` FROM `uuhms`.`med_pharm` GROUP BY `MED_ID`) p LEFT JOIN (SELECT `MED_ID` FROM `uuhms`.`med_store` GROUP BY `MED_ID`) s ON s.`MED_ID` = p.`MED_ID` WHERE s.`MED_ID` IS NULL) AS pharmacy_only_product_count, (SELECT COUNT(*) FROM (SELECT `MED_ID` FROM `uuhms`.`med_store` GROUP BY `MED_ID`) s LEFT JOIN (SELECT `MED_ID` FROM `uuhms`.`med_pharm` GROUP BY `MED_ID`) p ON p.`MED_ID` = s.`MED_ID` WHERE p.`MED_ID` IS NULL) AS store_only_product_count',
            'batch_vs_combined_snapshot.product_reconciliation' => 'SELECT COUNT(*) AS shared_product_count, SUM(b.qty = x.qty) AS equal_quantity_product_count, ROUND(SUM(b.qty - x.qty), 4) AS aggregate_quantity_difference FROM (SELECT `MED_ID`, SUM(CAST(`Qty` AS DECIMAL(20,4))) qty FROM `uuhms`.`batch` GROUP BY `MED_ID`) b INNER JOIN (SELECT `MED_ID`, SUM(qty) qty FROM (SELECT `MED_ID`, CAST(`Qty` AS DECIMAL(20,4)) qty FROM `uuhms`.`med_pharm` UNION ALL SELECT `MED_ID`, CAST(`Qty` AS DECIMAL(20,4)) qty FROM `uuhms`.`med_store`) snapshots GROUP BY `MED_ID`) x ON x.`MED_ID` = b.`MED_ID`',
            'batch_vs_combined_snapshot.unmatched_products' => 'SELECT (SELECT COUNT(*) FROM (SELECT `MED_ID` FROM `uuhms`.`batch` GROUP BY `MED_ID`) b LEFT JOIN (SELECT `MED_ID` FROM `uuhms`.`med_pharm` UNION SELECT `MED_ID` FROM `uuhms`.`med_store`) x ON x.`MED_ID` = b.`MED_ID` WHERE x.`MED_ID` IS NULL) AS batch_only_product_count, (SELECT COUNT(*) FROM (SELECT `MED_ID` FROM `uuhms`.`med_pharm` UNION SELECT `MED_ID` FROM `uuhms`.`med_store`) x LEFT JOIN (SELECT `MED_ID` FROM `uuhms`.`batch` GROUP BY `MED_ID`) b ON b.`MED_ID` = x.`MED_ID` WHERE b.`MED_ID` IS NULL) AS snapshot_only_product_count',
        ];
        $results = [];
        foreach ($definitions as $id => $sql) {
            $results[$id] = $this->numericRow($query->select('classic.stock.'.$id, $sql, purpose: 'Stock snapshot aggregate; no product identities or names returned.')[0]);
        }
        $results['interpretation'] = [
            'confirmed' => 'Classic contains stock snapshots, batches and requests, not a complete stock-movement ledger.',
            'decision_required' => 'Approve source-evidenced facts and/or a labelled opening position; never synthesize full movement history.',
        ];

        return $results;
    }

    /** @param array<int,string> $tableNames @param array<int,array<string,mixed>> $columns @param array<int,array<string,mixed>> $indexes @return array<int,array<string,mixed>> */
    private function incrementalCapabilities(array $tableNames, array $columns, array $indexes): array
    {
        $byTable = [];
        foreach ($columns as $column) {
            $byTable[$column['TABLE_NAME']][] = $column;
        }
        $primary = [];
        foreach ($indexes as $index) {
            if ($index['INDEX_NAME'] === 'PRIMARY') {
                $primary[$index['TABLE_NAME']][] = $index['COLUMN_NAME'];
            }
        }

        $matrix = [];
        foreach ($tableNames as $table) {
            $dateColumns = [];
            $mutableTimestamps = [];
            $temporalSemantics = [];
            foreach ($byTable[$table] ?? [] as $column) {
                if (in_array(strtolower((string) $column['DATA_TYPE']), ['date', 'datetime', 'timestamp'], true)) {
                    $dateColumns[] = $column['COLUMN_NAME'];
                    if (str_contains(strtolower((string) $column['EXTRA']), 'on update')) {
                        $mutableTimestamps[] = $column['COLUMN_NAME'];
                        $temporalSemantics[$column['COLUMN_NAME']] = 'schema-managed mutable timestamp candidate; updates may be visible but deletes are not';
                    } else {
                        $temporalSemantics[$column['COLUMN_NAME']] = strtolower((string) $column['DATA_TYPE']) === 'date'
                            ? 'business date; not a row-change watermark'
                            : 'event/creation timestamp candidate; update semantics are not evidenced';
                    }
                }
            }
            $pk = $primary[$table] ?? [];
            $strategy = match (true) {
                $pk === [] => 'full_snapshot_with_canonical_row_hash; no key checkpoint',
                $dateColumns === [] => 'primary-key high-water detects inserts only; periodic full snapshot required for updates/deletes',
                $mutableTimestamps !== [] => '(mutable timestamp, primary key) watermark plus periodic full snapshot; semantics must be approved',
                default => '(event/creation timestamp, primary key) may detect inserts only; periodic full snapshot required for updates/deletes',
            };
            $matrix[] = [
                'table' => $table,
                'primary_key_columns' => $pk,
                'date_or_timestamp_columns' => $dateColumns,
                'on_update_timestamp_columns' => $mutableTimestamps,
                'temporal_column_semantics' => $temporalSemantics,
                'candidate_strategy' => $strategy,
                'approved' => false,
                'blocking_decision' => 'D-210/Q-401/Q-402',
            ];
        }

        return $matrix;
    }

    /** @param array<int,array<string,mixed>> $matrix */
    private function incrementalMarkdown(array $matrix, string $executedAt): string
    {
        $lines = [
            '# Classic incremental extraction capability matrix', '',
            'Generated at `'.$executedAt.'` by the SELECT-only Classic evidence command.', '',
            'All strategies are **candidates**, not approved synchronization policy. Classic has no general delete/change journal.', '',
            '| Table | Primary key | Date/timestamp semantics | `ON UPDATE` columns | Candidate extraction treatment | Approved |',
            '|---|---|---|---|---|---|',
        ];
        foreach ($matrix as $row) {
            $semantics = $row['temporal_column_semantics'] === []
                ? 'None'
                : implode('; ', array_map(fn (string $column, string $meaning): string => '`'.$column.'`: '.$meaning, array_keys($row['temporal_column_semantics']), array_values($row['temporal_column_semantics'])));
            $lines[] = '| `'.$row['table'].'` | '.$this->mdArray($row['primary_key_columns']).' | '.$semantics.' | '.$this->mdArray($row['on_update_timestamp_columns']).' | '.$row['candidate_strategy'].' | No |';
        }
        $lines[] = '';
        $lines[] = '## Blocking decisions';
        $lines[] = '';
        $lines[] = '- D-210/Q-401/Q-402: approve snapshot/change-capture, timestamp semantics, delete detection, tie-breaking and keyless-table identity.';
        $lines[] = '- `serv_options_cri` and `serv_options_out` are keyless and require canonical full-snapshot hashing.';

        return implode("\n", $lines)."\n";
    }

    private function safeOutputPath(string $outputDirectory): string
    {
        $normalized = str_replace('\\', '/', trim($outputDirectory, '/\\ '));
        if ($normalized === '' || str_contains($normalized, '..') || ($normalized !== 'docs/legacy-migration/evidence' && ! str_starts_with($normalized, 'docs/legacy-migration/evidence/'))) {
            throw new RuntimeException('Classic evidence output must stay under docs/legacy-migration/evidence.');
        }

        return base_path(str_replace('/', DIRECTORY_SEPARATOR, $normalized));
    }

    private function assertIdentifier(string $identifier): void
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new RuntimeException('Unsafe metadata identifier rejected.');
        }
    }

    private function quote(string $identifier): string
    {
        $this->assertIdentifier($identifier);

        return '`'.$identifier.'`';
    }

    private function qualified(string $table): string
    {
        return '`'.self::APPROVED_DATABASE.'`.'.$this->quote($table);
    }

    /** @param array<int,object> $rows @return array<int,array<string,mixed>> */
    private function rows(array $rows): array
    {
        return array_map(fn (object $row): array => (array) $row, $rows);
    }

    /** @return array<string,mixed> */
    private function numericRow(object $row): array
    {
        $result = [];
        foreach ((array) $row as $key => $value) {
            if ($value === null) {
                $result[$key] = 0;
            } elseif (is_numeric($value)) {
                $result[$key] = str_contains((string) $value, '.') ? (string) $value : (int) $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function normalizeSql(string $sql): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $sql));
    }

    private function toolCodeHash(): string
    {
        $files = [
            'ClassicEvidenceCaptureService.php' => __FILE__,
            'ReadOnlyQueryRecorder.php' => __DIR__.DIRECTORY_SEPARATOR.'ReadOnlyQueryRecorder.php',
            'LegacyMigrationCaptureClassicEvidenceCommand.php' => app_path('Console/Commands/LegacyMigrationCaptureClassicEvidenceCommand.php'),
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

    /** @param array<int,string> $values */
    private function mdArray(array $values): string
    {
        return $values === [] ? 'None' : implode(', ', array_map(fn (string $value): string => '`'.$value.'`', $values));
    }
}
