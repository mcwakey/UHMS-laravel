<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteLoadMemoryTest extends TestCase
{
    public function test_application_routes_load_within_test_memory_budget(): void
    {
        $this->assertSame('512M', ini_get('memory_limit'));

        $routes = Route::getRoutes();
        $routeCount = $routes->count();
        $peakBytes = memory_get_peak_usage(true);
        $budgetBytes = 480 * 1024 * 1024;

        $this->assertGreaterThan(1000, $routeCount);
        $this->assertLessThan(
            $budgetBytes,
            $peakBytes,
            sprintf('Route load peak memory was %.2f MB for %d routes.', $peakBytes / 1024 / 1024, $routeCount),
        );
    }

    public function test_route_list_command_can_be_generated(): void
    {
        $exitCode = Artisan::call('route:list', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertGreaterThan(1000, count($payload));
    }
}
