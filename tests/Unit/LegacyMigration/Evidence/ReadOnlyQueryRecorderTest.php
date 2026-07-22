<?php

namespace Tests\Unit\LegacyMigration\Evidence;

use App\Services\LegacyMigration\Evidence\ReadOnlyQueryRecorder;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ReadOnlyQueryRecorderTest extends TestCase
{
    #[Test]
    #[DataProvider('unsafeQueries')]
    public function it_rejects_non_read_only_or_side_effecting_sql(string $sql): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->never())->method('select');
        $recorder = new ReadOnlyQueryRecorder($connection, 'test/1');

        $this->expectException(RuntimeException::class);
        $recorder->select('unsafe', $sql);
    }

    /** @return array<string, array{string}> */
    public static function unsafeQueries(): array
    {
        return [
            'update' => ['UPDATE patients SET name = name'],
            'multiple statements' => ['SELECT 1; SELECT 2'],
            'outfile' => ["SELECT 'x' INTO OUTFILE '/tmp/evidence'"],
            'sleep function' => ['SELECT SLEEP(1)'],
            'stored procedure' => ['CALL evidence_probe()'],
        ];
    }

    #[Test]
    public function it_records_hashes_times_results_and_only_safe_schema_bindings(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('select')
            ->with('SELECT ? AS approved_database, 2 AS aggregate_count', ['uuhms'])
            ->willReturn([(object) ['approved_database' => 'uuhms', 'aggregate_count' => 2]]);
        $recorder = new ReadOnlyQueryRecorder($connection, 'test/1');

        $recorder->select('safe.aggregate', 'SELECT ? AS approved_database, 2 AS aggregate_count', ['uuhms'], 'Aggregate test.');
        $manifest = $recorder->manifest('2026-01-01T00:00:00+00:00', '2026-01-01T00:00:01+00:00', 'uuhms');
        $query = $manifest['queries'][0];

        $this->assertSame(2, $manifest['manifest_version']);
        $this->assertSame(['uuhms'], $query['bindings']);
        $this->assertSame(64, strlen($query['query_hash_sha256']));
        $this->assertSame(64, strlen($query['bindings_hash_sha256']));
        $this->assertSame(64, strlen($query['result_hash_sha256']));
        $this->assertSame(1, $query['row_count_returned']);
        $this->assertArrayHasKey('started_at_utc', $query);
        $this->assertArrayHasKey('finished_at_utc', $query);
    }

    #[Test]
    public function it_rejects_duplicate_query_ids(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('select')->willReturn([]);
        $recorder = new ReadOnlyQueryRecorder($connection, 'test/1');
        $recorder->select('same-id', 'SELECT 1');

        $this->expectException(RuntimeException::class);
        $recorder->select('same-id', 'SELECT 2');
    }
}
