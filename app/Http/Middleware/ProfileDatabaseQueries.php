<?php

namespace App\Http\Middleware;

use App\Support\DatabaseQueryProfiler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProfileDatabaseQueries
{
    public function __construct(private DatabaseQueryProfiler $profiler) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.profiling.enabled') || ! app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $this->profiler->start();

        try {
            $response = $next($request);
        } finally {
            $report = $this->profiler->stop();
        }

        $requestTimeMs = round((hrtime(true) - $startedAt) / 1_000_000, 2);

        if (config('performance.profiling.response_headers')) {
            $response->headers->set('X-UHMS-Query-Count', (string) $report['total']);
            $response->headers->set('X-UHMS-Query-Unique', (string) $report['unique']);
            $response->headers->set('X-UHMS-Query-Duplicates', (string) $report['duplicate']);
            $response->headers->set('X-UHMS-Database-Time-Ms', (string) $report['database_time_ms']);
            $response->headers->set('X-UHMS-Request-Time-Ms', (string) $requestTimeMs);
        }

        if ($report['total'] >= config('performance.profiling.warning_query_count')) {
            Log::warning('UHMS request exceeded the local query warning threshold.', [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'path' => $request->path(),
                'query_count' => $report['total'],
                'unique_query_count' => $report['unique'],
                'duplicate_query_count' => $report['duplicate'],
                'database_time_ms' => $report['database_time_ms'],
                'request_time_ms' => $requestTimeMs,
                'top_patterns' => array_slice($report['patterns'], 0, 10),
            ]);
        }

        return $response;
    }
}
