<?php

namespace Tests\Unit\LegacyMigration\Foundation\Environment;

use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use App\Services\LegacyMigration\Foundation\Environment\SourceAccountVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SourceAccountVerifierTest extends TestCase
{
    #[Test]
    public function select_and_metadata_only_grants_on_exact_uuhms_pass_with_redacted_report(): void
    {
        $connection = $this->connection([
            ['Grants for account' => 'GRANT USAGE ON *.* TO `migration_reader`@`private-host`'],
            ['Grants for account' => 'GRANT SELECT, SHOW VIEW ON `uuhms`.* TO `migration_reader`@`private-host`'],
        ]);
        $connection->beginReadOnlySnapshot();

        $verification = (new SourceAccountVerifier)->verify($connection);
        $report = $verification->safeReport();

        $this->assertSame(['SELECT', 'SHOW VIEW', 'USAGE'], $verification->privileges);
        $this->assertSame('redacted', $report['account_identity']);
        $this->assertStringNotContainsString('migration_reader', json_encode($report, JSON_THROW_ON_ERROR));
        $this->assertContains('SHOW GRANTS', $connection->queries);
        $this->assertCount(9, $connection->queries);
        $this->assertContains('table', $report['inspected_surfaces']);
        $this->assertContains('routine', $report['inspected_surfaces']);
        $this->assertSame(55, $report['select_coverage_table_count']);
        $this->assertSame('schema_select_all_55', $report['select_coverage_mode']);
    }

    #[Test]
    public function exact_table_grants_must_cover_all_55_approved_tables(): void
    {
        $grants = [['Grants for account' => 'GRANT USAGE ON *.* TO `reader`@`host`']];
        foreach (range(1, 55) as $ordinal) {
            $grants[] = ['Grants for account' => sprintf('GRANT SELECT ON `uuhms`.`table_%02d` TO `reader`@`host`', $ordinal)];
        }
        $connection = $this->connection($grants);
        $connection->beginReadOnlySnapshot();
        self::assertContains('SELECT', (new SourceAccountVerifier)->verify($connection)->privileges);

        array_pop($grants);
        $connection = $this->connection($grants);
        $connection->beginReadOnlySnapshot();
        try {
            (new SourceAccountVerifier)->verify($connection);
            self::fail('Expected incomplete table coverage to fail closed.');
        } catch (FoundationGuardException $exception) {
            self::assertSame('FOUNDATION_SOURCE_SELECT_COVERAGE_INCOMPLETE', $exception->faultCode);
        }
    }

    #[Test]
    public function broad_or_write_capable_grants_fail_closed_without_echoing_grant(): void
    {
        $connection = $this->connection([
            ['Grants for account' => 'GRANT SELECT, INSERT ON `uuhms`.* TO `sensitive-user`@`sensitive-host`'],
        ]);
        $connection->beginReadOnlySnapshot();

        try {
            (new SourceAccountVerifier)->verify($connection);
            $this->fail('Expected write-capable account rejection.');
        } catch (FoundationGuardException $exception) {
            $this->assertSame('FOUNDATION_SOURCE_ACCOUNT_WRITE_CAPABLE', $exception->faultCode);
            $this->assertStringNotContainsString('sensitive-user', $exception->getMessage());
            $this->assertStringNotContainsString('INSERT', $exception->getMessage());
        }
    }

    #[Test]
    public function select_on_any_other_database_fails_closed(): void
    {
        $connection = $this->connection([
            ['Grants for account' => 'GRANT SELECT ON `other_database`.* TO `reader`@`host`'],
        ]);
        $connection->beginReadOnlySnapshot();

        $this->expectException(FoundationGuardException::class);
        $this->expectExceptionMessage('unapproved grant scope');
        (new SourceAccountVerifier)->verify($connection);
    }

    #[Test]
    public function transaction_must_be_proven_read_only(): void
    {
        $connection = new FakeMetadataConnection('legacy_uhms', 'uuhms', static fn (): array => [[
            'database_name' => 'uuhms', 'transaction_read_only' => 0,
        ]]);
        $connection->beginReadOnlySnapshot();

        try {
            (new SourceAccountVerifier)->verify($connection);
            $this->fail('Expected read-only proof rejection.');
        } catch (FoundationGuardException $exception) {
            $this->assertSame('FOUNDATION_READ_ONLY_UNPROVEN', $exception->faultCode);
        }
    }

    #[Test]
    public function table_column_routine_role_wildcard_and_cross_schema_privileges_fail_closed(): void
    {
        foreach ([
            'table_insert' => ['surface' => 'TABLE_PRIVILEGES', 'row' => ['TABLE_SCHEMA' => 'uuhms', 'TABLE_NAME' => 'patients', 'PRIVILEGE_TYPE' => 'INSERT', 'IS_GRANTABLE' => 'NO']],
            'table_update' => ['surface' => 'TABLE_PRIVILEGES', 'row' => ['TABLE_SCHEMA' => 'uuhms', 'TABLE_NAME' => 'patients', 'PRIVILEGE_TYPE' => 'UPDATE', 'IS_GRANTABLE' => 'NO']],
            'table_delete' => ['surface' => 'TABLE_PRIVILEGES', 'row' => ['TABLE_SCHEMA' => 'uuhms', 'TABLE_NAME' => 'patients', 'PRIVILEGE_TYPE' => 'DELETE', 'IS_GRANTABLE' => 'NO']],
            'cross_schema_select' => ['surface' => 'TABLE_PRIVILEGES', 'row' => ['TABLE_SCHEMA' => 'another', 'TABLE_NAME' => 'patients', 'PRIVILEGE_TYPE' => 'SELECT', 'IS_GRANTABLE' => 'NO']],
            'routine_execute' => ['surface' => 'ROUTINE_PRIVILEGES', 'row' => ['ROUTINE_SCHEMA' => 'uuhms', 'ROUTINE_NAME' => 'unsafe', 'PRIVILEGE_TYPE' => 'EXECUTE', 'IS_GRANTABLE' => 'NO']],
        ] as $case) {
            $connection = $this->connection([
                ['Grants for account' => 'GRANT USAGE ON *.* TO `reader`@`host`'],
                ['Grants for account' => 'GRANT SELECT ON `uuhms`.* TO `reader`@`host`'],
            ], $case['surface'], [$case['row']]);
            $connection->beginReadOnlySnapshot();
            try {
                (new SourceAccountVerifier)->verify($connection);
                self::fail('Expected privilege-surface rejection for '.$case['surface']);
            } catch (FoundationGuardException $exception) {
                self::assertStringNotContainsString('patients', $exception->getMessage());
                self::assertStringNotContainsString('unsafe', $exception->getMessage());
            }
        }

        foreach ([
            'GRANT `writer_role` TO `reader`@`host`',
            'GRANT SELECT ON `uuhm%`.* TO `reader`@`host`',
            'GRANT PROXY ON `admin`@`host` TO `reader`@`host`',
        ] as $grant) {
            $connection = $this->connection([['Grants for account' => $grant]]);
            $connection->beginReadOnlySnapshot();
            $this->expectVerifierFailure($connection);
        }
    }

    /** @param array<int, array<string, string>> $grants */
    private function connection(array $grants, ?string $surface = null, array $surfaceRows = []): FakeMetadataConnection
    {
        return new FakeMetadataConnection('legacy_uhms', 'uuhms', static function (string $sql) use ($grants, $surface, $surfaceRows): array {
            if ($sql === 'SHOW GRANTS') {
                return $grants;
            }

            if ($sql === 'SELECT CURRENT_ROLE() AS active_roles') {
                return [['active_roles' => 'NONE']];
            }

            if (str_contains($sql, "TABLE_SCHEMA = 'uuhms'") && str_contains($sql, "TABLE_TYPE = 'BASE TABLE'")) {
                return array_map(static fn (int $ordinal): array => [
                    'TABLE_NAME' => sprintf('table_%02d', $ordinal),
                ], range(1, 55));
            }

            foreach (['USER_PRIVILEGES', 'SCHEMA_PRIVILEGES', 'TABLE_PRIVILEGES', 'COLUMN_PRIVILEGES', 'ROUTINE_PRIVILEGES'] as $name) {
                if (str_contains($sql, $name)) {
                    return $surface === $name ? $surfaceRows : [];
                }
            }

            return [['database_name' => 'uuhms', 'transaction_read_only' => 1]];
        });
    }

    private function expectVerifierFailure(FakeMetadataConnection $connection): void
    {
        try {
            (new SourceAccountVerifier)->verify($connection);
            self::fail('Expected unsafe grant rejection.');
        } catch (FoundationGuardException $exception) {
            self::assertStringNotContainsString('writer_role', $exception->getMessage());
            self::assertStringNotContainsString('uuhm%', $exception->getMessage());
        }
    }
}
