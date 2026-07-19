<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;

class DatabaseQueryProfiler
{
    private bool $active = false;

    private int $queryCount = 0;

    private float $databaseTimeMs = 0.0;

    /** @var array<string, array{count: int, time_ms: float, connection: string}> */
    private array $fingerprints = [];

    public function start(): void
    {
        $this->active = true;
        $this->queryCount = 0;
        $this->databaseTimeMs = 0.0;
        $this->fingerprints = [];
    }

    public function stop(): array
    {
        $this->active = false;

        return $this->report();
    }

    public function record(QueryExecuted $query): void
    {
        if (! $this->active) {
            return;
        }

        $fingerprint = $this->fingerprint($query->sql);
        $this->queryCount++;
        $this->databaseTimeMs += $query->time;

        $entry = $this->fingerprints[$fingerprint] ?? [
            'count' => 0,
            'time_ms' => 0.0,
            'connection' => $query->connectionName,
        ];
        $entry['count']++;
        $entry['time_ms'] += $query->time;
        $this->fingerprints[$fingerprint] = $entry;
    }

    /** @return array{total: int, unique: int, duplicate: int, database_time_ms: float, patterns: array<int, array{fingerprint: string, count: int, time_ms: float, connection: string}>} */
    public function report(): array
    {
        $patterns = [];

        foreach ($this->fingerprints as $fingerprint => $metrics) {
            $patterns[] = [
                'fingerprint' => $fingerprint,
                'count' => $metrics['count'],
                'time_ms' => round($metrics['time_ms'], 2),
                'connection' => $metrics['connection'],
            ];
        }

        usort($patterns, fn (array $left, array $right) => [$right['count'], $right['time_ms']] <=> [$left['count'], $left['time_ms']]);

        return [
            'total' => $this->queryCount,
            'unique' => count($this->fingerprints),
            'duplicate' => max(0, $this->queryCount - count($this->fingerprints)),
            'database_time_ms' => round($this->databaseTimeMs, 2),
            'patterns' => $patterns,
        ];
    }

    private function fingerprint(string $sql): string
    {
        $normalised = preg_replace("/'(?:''|[^'])*'/", '?', $sql) ?? $sql;
        $normalised = preg_replace('/\b(?:0x)?[0-9a-f]{8,}\b/i', '?', $normalised) ?? $normalised;
        $normalised = preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $normalised) ?? $normalised;
        $normalised = preg_replace('/\s+/', ' ', trim($normalised)) ?? trim($normalised);

        return strtolower($normalised);
    }
}
