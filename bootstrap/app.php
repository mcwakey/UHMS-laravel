<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('notifications:cleanup')->dailyAt('02:30')->withoutOverlapping();
        $schedule->command('logs:cleanup')->dailyAt('02:45')->withoutOverlapping();
        $schedule->command('medications:check-overdue')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('clinical-tasks:check-due')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('claims:check-stale')->dailyAt('06:00')->withoutOverlapping();
        $schedule->command('blood-bank:expire-units')->dailyAt('05:30')->withoutOverlapping();
        $schedule->command('blood-bank:notify-expiring --days=7')->dailyAt('06:15')->withoutOverlapping();
        $schedule->command('notifications:check-escalations')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('notifications:flush-digest')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('outpatient-sessions:auto-complete')->dailyAt('00:10')->withoutOverlapping();
        $schedule->command('reports:notifications-summary')->monthlyOn(1, '06:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'   => \App\Http\Middleware\EnsureUserHasRole::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Inertia.js — appended to the web group so any future Inertia
        // controller response automatically receives shared props.
        // Harmless on classic Blade responses (no-ops unless an
        // Inertia\Response is returned).
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ConvertBladeViewsToInertia::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $redirectInertiaToLogin = function (\Illuminate\Http\Request $request) {
            if ($request->hasSession()) {
                $intendedUrl = $request->isMethod('GET')
                    ? $request->fullUrl()
                    : $request->headers->get('referer');

                if ($intendedUrl) {
                    $request->session()->put('url.intended', $intendedUrl);
                }
            }

            return \Inertia\Inertia::location(route('login'));
        };

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) use ($redirectInertiaToLogin) {
            if ($request->headers->has('X-Inertia')) {
                return $redirectInertiaToLogin($request);
            }

            return null;
        });

        // Production shield for raw database errors. The full technical detail is
        // always logged; users never see SQLSTATE, SQL, table/column names or a
        // stack trace. In debug mode developers still get the full Laravel page.
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error('Database error shielded from user', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'sql_state' => $e->getCode(),
                'user_id' => optional($request->user())->getAuthIdentifier(),
                'route' => optional($request->route())->getName(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            if (config('app.debug')) {
                return null;
            }

            $path = $request->path();
            $friendly = match (true) {
                str_contains($path, 'stock') || str_contains($path, 'store') || str_contains($path, 'inventory')
                    => 'Unable to complete this stock operation. Please check that the product and stock location are configured correctly, then try again.',
                str_contains($path, 'billing') || str_contains($path, 'invoice') || str_contains($path, 'payment')
                    => 'Unable to complete this billing operation. Please verify the invoice item details and try again.',
                default
                    => 'Unable to complete the request because some required information is missing or invalid. Please try again, or contact the system administrator if the problem continues.',
            };

            if ($request->expectsJson()) {
                return response()->json(['message' => $friendly], 500);
            }

            // Form submissions: keep the user in context with a friendly flash
            // rather than a full-screen error page.
            if (! $request->isMethod('GET') && $request->hasSession()) {
                return back()->withInput()->with('error', $friendly);
            }

            return response()->view('errors.500', [], 500);
        });

        $exceptions->respond(function ($response, \Throwable $e, \Illuminate\Http\Request $request) use ($redirectInertiaToLogin) {
            if ($request->headers->has('X-Inertia') && in_array($response->getStatusCode(), [401, 419], true)) {
                return $redirectInertiaToLogin($request);
            }

            return $response;
        });
    })->create();
