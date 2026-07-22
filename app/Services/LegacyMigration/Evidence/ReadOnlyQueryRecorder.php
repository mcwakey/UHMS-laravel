<?php

namespace App\Services\LegacyMigration\Evidence;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final class ReadOnlyQueryRecorder
{
    /** @var array<int, array<string, mixed>> */
    private array $queries = [];

    /** @var array<string, true> */
    private array $queryIds = [];

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $toolVersion,
    ) {}

    /**
     * @param  array<int, mixed>  $bindings
     * @return array<int, object>
     */
    public function select(string $id, string $sql, array $bindings = [], string $purpose = ''): array
    {
        $normalized = $this->normalize($sql);

        if (isset($this->queryIds[$id])) {
            throw new RuntimeException("Duplicate evidence query id [{$id}] was rejected.");
        }

        if (! preg_match('/^SELECT\b/i', $normalized)) {
            throw new RuntimeException("Evidence query [{$id}] was rejected because it is not SELECT-only.");
        }

        if (str_contains($normalized, ';')) {
            throw new RuntimeException("Evidence query [{$id}] was rejected because statement separators are forbidden.");
        }

        if (preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE|CREATE|GRANT|REVOKE|LOCK|UNLOCK|CALL|DO|SET|LOAD|HANDLER)\b/i', $normalized)) {
            throw new RuntimeException("Evidence query [{$id}] contains a forbidden SQL keyword.");
        }

        if (preg_match('/\bINTO\s+(?:OUTFILE|DUMPFILE)\b|\b(?:SLEEP|BENCHMARK|GET_LOCK|RELEASE_LOCK)\s*\(/i', $normalized)) {
            throw new RuntimeException("Evidence query [{$id}] contains a forbidden side-effecting SELECT construct.");
        }

        $startedAtUtc = gmdate('c');
        $startedAt = microtime(true);
        $rows = $this->connection->select($sql, $bindings);
        $finishedAtUtc = gmdate('c');

        $this->queryIds[$id] = true;

        $this->queries[] = [
            'id' => $id,
            'query_version' => 1,
            'purpose' => $purpose,
            'normalized_sql' => $normalized,
            'query_hash_sha256' => hash('sha256', $normalized),
            'bindings' => $bindings,
            'bindings_hash_sha256' => hash('sha256', $this->canonicalJson($bindings)),
            'binding_count' => count($bindings),
            'started_at_utc' => $startedAtUtc,
            'finished_at_utc' => $finishedAtUtc,
            'row_count_returned' => count($rows),
            'result_hash_sha256' => hash('sha256', $this->canonicalJson(array_map(fn (object $row): array => (array) $row, $rows))),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 3),
        ];

        return $rows;
    }

    /** @return array<string, mixed> */
    /** @param array<string, mixed> $runMetadata */
    public function manifest(string $startedAt, string $finishedAt, string $database, array $runMetadata = []): array
    {
        return array_merge([
            'manifest_version' => 2,
            'tool_version' => $this->toolVersion,
            'started_at_utc' => $startedAt,
            'finished_at_utc' => $finishedAt,
            'approved_database' => $database,
            'safety' => [
                'sql_policy' => 'Trusted static SELECT statements only; separators, mutating/admin keywords, outfile/dumpfile and selected side-effecting functions are rejected',
                'limitation' => 'This defense-in-depth filter is not a general SQL parser. Database-enforced read-only access remains mandatory for migration execution.',
                'credentials_included' => false,
                'record_level_identifiers_included' => false,
            ],
            'queries' => array_map(function (array $query) use ($database): array {
                $query['bindings'] = array_map(
                    fn (mixed $binding): mixed => is_string($binding) && hash_equals($database, $binding) ? $binding : '[redacted; verify with bindings_hash_sha256]',
                    $query['bindings'],
                );

                return $query;
            }, $this->queries),
        ], $runMetadata);
    }

    /** @return array<string, mixed> */
    public function evidenceFor(string $id): array
    {
        foreach ($this->queries as $query) {
            if ($query['id'] === $id) {
                return [
                    'query_id' => $id,
                    'query_version' => $query['query_version'],
                    'query_hash_sha256' => $query['query_hash_sha256'],
                    'bindings_hash_sha256' => $query['bindings_hash_sha256'],
                    'started_at_utc' => $query['started_at_utc'],
                    'finished_at_utc' => $query['finished_at_utc'],
                    'result_hash_sha256' => $query['result_hash_sha256'],
                ];
            }
        }

        throw new RuntimeException("No evidence was recorded for query [{$id}].");
    }

    private function normalize(string $sql): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $sql));
    }

    private function canonicalJson(mixed $value): string
    {
        return json_encode($this->canonicalize($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
