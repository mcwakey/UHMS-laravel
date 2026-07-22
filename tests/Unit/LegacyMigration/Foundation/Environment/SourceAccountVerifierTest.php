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
            ["Grants for account" => "GRANT USAGE ON *.* TO `migration_reader`@`private-host`"],
            ["Grants for account" => "GRANT SELECT, SHOW VIEW ON `uuhms`.* TO `migration_reader`@`private-host`"],
        ]);
        $connection->beginReadOnlySnapshot();

        $verification = (new SourceAccountVerifier)->verify($connection);
        $report = $verification->safeReport();

        $this->assertSame(['SELECT', 'SHOW VIEW', 'USAGE'], $verification->privileges);
        $this->assertSame('redacted', $report['account_identity']);
        $this->assertStringNotContainsString('migration_reader', json_encode($report, JSON_THROW_ON_ERROR));
        $this->assertSame(['SELECT DATABASE() AS database_name, @@session.tx_read_only AS transaction_read_only', 'SHOW GRANTS'], $connection->queries);
    }

    #[Test]
    public function broad_or_write_capable_grants_fail_closed_without_echoing_grant(): void
    {
        $connection = $this->connection([
            ["Grants for account" => "GRANT SELECT, INSERT ON `uuhms`.* TO `sensitive-user`@`sensitive-host`"],
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
            ["Grants for account" => "GRANT SELECT ON `other_database`.* TO `reader`@`host`"],
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

    /** @param array<int, array<string, string>> $grants */
    private function connection(array $grants): FakeMetadataConnection
    {
        return new FakeMetadataConnection('legacy_uhms', 'uuhms', static function (string $sql) use ($grants): array {
            if ($sql === 'SHOW GRANTS') {
                return $grants;
            }

            return [['database_name' => 'uuhms', 'transaction_read_only' => 1]];
        });
    }
}
