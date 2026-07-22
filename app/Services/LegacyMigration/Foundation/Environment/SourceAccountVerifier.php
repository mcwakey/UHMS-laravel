<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final class SourceAccountVerifier
{
    /** @var array<int, string> */
    private const ALLOWED_PRIVILEGES = ['USAGE', 'SELECT', 'SHOW VIEW'];

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
        foreach ($rows as $row) {
            if (count($row) !== 1 || ! is_string(reset($row))) {
                throw new FoundationGuardException('FOUNDATION_GRANT_UNRECOGNIZED', 'A Classic account grant was not recognized.');
            }
            $grant = trim((string) reset($row));
            if (preg_match('/\AWITH GRANT OPTION\z/i', $grant) === 1
                || stripos($grant, ' WITH GRANT OPTION') !== false
                || preg_match('/\AGRANT\s+`?[^`\s]+`?@/i', $grant) === 1) {
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
                $privileges[] = $privilege;
            }
        }
        $privileges = array_values(array_unique($privileges));
        sort($privileges, SORT_STRING);
        if (! in_array('SELECT', $privileges, true)) {
            throw new FoundationGuardException('FOUNDATION_SOURCE_SELECT_MISSING', 'The Classic migration account does not have approved SELECT access.');
        }

        return new SourceAccountVerification($privileges, count($rows));
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
