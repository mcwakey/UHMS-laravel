<?php

namespace Tests\Concerns;

use App\Support\DatabaseQueryProfiler;

trait InteractsWithDatabaseQueryBudgets
{
    protected function measureDatabaseQueries(callable $callback): array
    {
        $profiler = app(DatabaseQueryProfiler::class);
        $profiler->start();
        $startedAt = hrtime(true);
        $memoryBefore = memory_get_usage(true);

        try {
            $result = $callback();
        } finally {
            $metrics = $profiler->stop();
        }

        $metrics['request_time_ms'] = round((hrtime(true) - $startedAt) / 1_000_000, 2);
        $metrics['memory_delta_bytes'] = max(0, memory_get_usage(true) - $memoryBefore);
        $metrics['result'] = $result ?? null;

        if (filter_var(getenv('UHMS_QUERY_BENCHMARK_OUTPUT'), FILTER_VALIDATE_BOOL)) {
            fwrite(STDERR, sprintf(
                "\n[query-budget] route=%s total=%d unique=%d duplicates=%d db_ms=%.2f request_ms=%.2f memory_bytes=%d\n",
                request()->route()?->getName() ?? 'unknown',
                $metrics['total'],
                $metrics['unique'],
                $metrics['duplicate'],
                $metrics['database_time_ms'],
                $metrics['request_time_ms'],
                $metrics['memory_delta_bytes'],
            ));
        }

        return $metrics;
    }

    protected function assertMaxDatabaseQueries(int $maximum, callable $callback): array
    {
        $metrics = $this->measureDatabaseQueries($callback);
        $topPatterns = collect($metrics['patterns'])
            ->take(10)
            ->map(fn (array $pattern) => sprintf('%dx %s', $pattern['count'], $pattern['fingerprint']))
            ->implode("\n");

        $this->assertLessThanOrEqual(
            $maximum,
            $metrics['total'],
            "Expected at most {$maximum} database queries; observed {$metrics['total']} ({$metrics['duplicate']} duplicates).\n{$topPatterns}",
        );

        return $metrics;
    }
}
