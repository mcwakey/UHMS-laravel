<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final class SourceAccountVerifier
{
    /** @var array<int, string> */
    private const ALLOWED_PRIVILEGES = ['USAGE', 'SELECT', 'SHOW VIEW'];

    /** @var list<string> */
    private const PRIVILEGE_SURFACES = ['global', 'schema', 'table', 'column', 'routine', 'role', 'proxy', 'wildcard', 'grant_option', 'administrative'];

    public function verify(MetadataConnection $connection): SourceAccountVerification
    {
        if ($connection->name() !== GuardConfiguration::SOURCE_CONNECTION
            || $connection->configuredDatabase() !== GuardConfiguration::SOURCE_DATABASE) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_COORDINATE_REJECTED', 'The configured Classic source coordinate is not approved.');
        }
        if (! $connection->readOnlySnapshotActive()) {
            throw new FoundationGuardException('FOUNDATION_SNAPSHOT_REQUIRED', 'A read-only source transaction is required.');
        }

        $state = $connection->select(
            'SELECT DATABASE() AS database_name, @@session.tx_read_only AS transaction_read_only'
        );
        if (count($state) !== 1
            || ($state[0]['database_name'] ?? null) !== GuardConfiguration::SOURCE_DATABASE
            || (int) ($state[0]['transaction_read_only'] ?? 0) !== 1) {
            throw new FoundationGuardException('FOUNDATION_READ_ONLY_UNPROVEN', 'The Classic source transaction is not proven read-only.');
        }

        $rows = $connection->select('SHOW GRANTS');
        if ($rows === []) {
            throw new FoundationGuardException('FOUNDATION_GRANTS_UNAVAILABLE', 'Classic account grants could not be verified.');
        }

        $privileges = [];
        $schemaSelect = false;
        $tableSelect = [];
        foreach ($rows as $row) {
            if (count($row) !== 1 || ! is_string(reset($row))) {
                throw new FoundationGuardException('FOUNDATION_GRANT_UNRECOGNIZED', 'A Classic account grant was not recognized.');
            }
            $grant = trim((string) reset($row));
            if (preg_match('/\AWITH GRANT OPTION\z/i', $grant) === 1
                || stripos($grant, ' WITH GRANT OPTION') !== false
                || preg_match('/\AGRANT\s+PROXY\s+/i', $grant) === 1
                || preg_match('/\AGRANT\s+`?[^`\s,]+`?(?:\s*,\s*`?[^`\s,]+`?)*\s+TO\s+/i', $grant) === 1) {
                throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', 'The Classic migration account has an unapproved grant.');
            }
            if (preg_match('/\AGRANT\s+(.+?)\s+ON\s+(.+?)\s+TO\s+/i', $grant, $matches) !== 1) {
                throw new FoundationGuardException('FOUNDATION_GRANT_UNRECOGNIZED', 'A Classic account grant was not recognized.');
            }
            $scope = $this->normalizeScope($matches[2]);
            $statementPrivileges = array_map(
                static fn (string $value): string => strtoupper(trim(str_replace('`', '', $value))),
                explode(',', $matches[1]),
            );
            foreach ($statementPrivileges as $privilege) {
                if (! in_array($privilege, self::ALLOWED_PRIVILEGES, true)) {
                    throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', 'The Classic migration account has an unapproved privilege.');
                }
                if ($privilege === 'USAGE' && $scope !== '*.*') {
                    throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_SCOPE_INVALID', 'The Classic migration account has an unapproved grant scope.');
                }
                if ($privilege !== 'USAGE' && ! str_starts_with($scope, 'uuhms.')) {
                    throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_SCOPE_INVALID', 'The Classic migration account can access an unapproved database.');
                }
                if ($privilege === 'SELECT') {
                    if ($scope === 'uuhms.*') {
                        $schemaSelect = true;
                    } else {
                        $tableSelect[substr($scope, strlen('uuhms.'))] = true;
                    }
                }
                $privileges[] = $privilege;
            }
        }
        $privileges = array_values(array_unique($privileges));
        sort($privileges, SORT_STRING);
        if (! in_array('SELECT', $privileges, true)) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_SELECT_MISSING', 'The Classic migration account does not have approved SELECT access.');
        }

        $approvedTables = $connection->select(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'uuhms' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
        );
        $tableNames = [];
        foreach ($approvedTables as $table) {
            $name = strtolower((string) ($table['TABLE_NAME'] ?? ''));
            if ($name === '' || isset($tableNames[$name])) {
                throw new FoundationGuardException('FOUNDATION_SOURCE_TABLE_CATALOGUE_INVALID', 'The Classic table catalogue is incomplete or ambiguous.');
            }
            $tableNames[$name] = true;
        }
        if (count($tableNames) !== GuardConfiguration::SOURCE_TABLE_COUNT) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_TABLE_CATALOGUE_INVALID', 'The Classic table catalogue does not match the approved 55-table scope.');
        }
        if (! $schemaSelect) {
            $missing = array_diff_key($tableNames, $tableSelect);
            $unexpected = array_diff_key($tableSelect, $tableNames);
            if ($missing !== [] || $unexpected !== []) {
                throw new FoundationGuardException('FOUNDATION_SOURCE_SELECT_COVERAGE_INCOMPLETE', 'The Classic account does not prove SELECT coverage for every approved table.');
            }
        }

        $surfaceRecords = $this->inspectPrivilegeSurfaces($connection);

        return new SourceAccountVerification(
            $privileges,
            count($rows),
            $surfaceRecords,
            self::PRIVILEGE_SURFACES,
            count($tableNames),
            $schemaSelect ? 'schema_select_all_55' : 'exact_table_select_all_55',
        );
    }

    private function inspectPrivilegeSurfaces(MetadataConnection $connection): int
    {
        $queries = [
            'global' => 'SELECT PRIVILEGE_TYPE, IS_GRANTABLE FROM information_schema.USER_PRIVILEGES WHERE GRANTEE = CONCAT(CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), 1), CHAR(39), CHAR(64), CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), -1), CHAR(39)) ORDER BY PRIVILEGE_TYPE',
            'schema' => 'SELECT TABLE_SCHEMA, PRIVILEGE_TYPE, IS_GRANTABLE FROM information_schema.SCHEMA_PRIVILEGES WHERE GRANTEE = CONCAT(CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), 1), CHAR(39), CHAR(64), CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), -1), CHAR(39)) ORDER BY TABLE_SCHEMA, PRIVILEGE_TYPE',
            'table' => 'SELECT TABLE_SCHEMA, TABLE_NAME, PRIVILEGE_TYPE, IS_GRANTABLE FROM information_schema.TABLE_PRIVILEGES WHERE GRANTEE = CONCAT(CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), 1), CHAR(39), CHAR(64), CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), -1), CHAR(39)) ORDER BY TABLE_SCHEMA, TABLE_NAME, PRIVILEGE_TYPE',
            'column' => 'SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, PRIVILEGE_TYPE, IS_GRANTABLE FROM information_schema.COLUMN_PRIVILEGES WHERE GRANTEE = CONCAT(CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), 1), CHAR(39), CHAR(64), CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), -1), CHAR(39)) ORDER BY TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, PRIVILEGE_TYPE',
            'routine' => 'SELECT ROUTINE_SCHEMA, ROUTINE_NAME, ROUTINE_TYPE, PRIVILEGE_TYPE, IS_GRANTABLE FROM information_schema.ROUTINE_PRIVILEGES WHERE GRANTEE = CONCAT(CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), 1), CHAR(39), CHAR(64), CHAR(39), SUBSTRING_INDEX(CURRENT_USER(), CHAR(64), -1), CHAR(39)) ORDER BY ROUTINE_SCHEMA, ROUTINE_NAME, PRIVILEGE_TYPE',
        ];
        $count = 0;
        foreach ($queries as $surface => $sql) {
            $records = $connection->select($sql);
            $count += count($records);
            foreach ($records as $record) {
                if (strtoupper((string) ($record['IS_GRANTABLE'] ?? 'NO')) !== 'NO') {
                    throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', 'The Classic migration account has an unapproved grant option.');
                }
                $privilege = strtoupper((string) ($record['PRIVILEGE_TYPE'] ?? ''));
                if ($surface === 'global') {
                    if ($privilege !== 'USAGE') {
                        throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', 'The Classic migration account has an unapproved global privilege.');
                    }

                    continue;
                }
                if ($surface === 'routine') {
                    throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', 'The Classic migration account has an unapproved routine privilege.');
                }
                if (! in_array($privilege, ['SELECT', 'SHOW VIEW'], true)
                    || (string) ($record['TABLE_SCHEMA'] ?? '') !== GuardConfiguration::SOURCE_DATABASE) {
                    throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_SCOPE_INVALID', 'The Classic migration account has an unapproved privilege scope.');
                }
            }
        }

        $roles = $connection->select('SELECT CURRENT_ROLE() AS active_roles');
        if (count($roles) !== 1 || ! in_array(strtoupper((string) ($roles[0]['active_roles'] ?? '')), ['NONE', 'NULL', ''], true)) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', 'The Classic migration account has an active role.');
        }

        return $count;
    }

    private function normalizeScope(string $scope): string
    {
        $scope = strtolower(str_replace(['`', ' '], '', trim($scope)));
        if ($scope === '*.*') {
            return $scope;
        }
        if (preg_match('/\Auuhms\.(?:\*|[a-z0-9_]+)\z/', $scope) !== 1) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_ACCOUNT_SCOPE_INVALID', 'The Classic migration account has an unapproved grant scope.');
        }

        return $scope;
    }
}
