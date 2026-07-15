<?php

use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Http\Middleware\EnsureActiveDepartmentType;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureNursingOpdScope;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectRecordsWorkspace;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
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

        // External Integrations (Phase 3). These commands are self-guarding: they
        // no-op unless the module is enabled, a provider is active and the relevant
        // toggle is on — so it is safe to schedule them unconditionally.
        $schedule->command('integrations:sms-reconcile-status')->hourly()->withoutOverlapping();
        $schedule->command('integrations:payments-recheck-pending')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('integrations:sms-send-appointment-reminders')->dailyAt('08:00')->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'module' => EnsureModuleEnabled::class,
            'department.type' => EnsureActiveDepartmentType::class,
            'records.redirect' => RedirectRecordsWorkspace::class,
            'nursing.opd.scope' => EnsureNursingOpdScope::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Inertia.js — appended to the web group so any future Inertia
        // controller response automatically receives shared props.
        // Harmless on classic Blade responses (no-ops unless an
        // Inertia\Response is returned).
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            ConvertBladeViewsToInertia::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $redirectInertiaToLogin = function (Request $request) {
            if ($request->hasSession()) {
                $intendedUrl = $request->isMethod('GET')
                    ? $request->fullUrl()
                    : $request->headers->get('referer');

                if ($intendedUrl) {
                    $request->session()->put('url.intended', $intendedUrl);
                }
            }

            return Inertia::location(route('login'));
        };

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($redirectInertiaToLogin) {
            if ($request->headers->has('X-Inertia')) {
                return $redirectInertiaToLogin($request);
            }

            return null;
        });

        // Production shield for raw database errors. The full technical detail is
        // always logged; users never see SQLSTATE, SQL, table/column names or a
        // stack trace. In debug mode developers still get the full Laravel page.
        $exceptions->render(function (QueryException $e, Request $request) {
            Log::error('Database error shielded from user', [
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
                str_contains($path, 'stock') || str_contains($path, 'store') || str_contains($path, 'inventory') => 'Unable to complete this stock operation. Please check that the product and stock location are configured correctly, then try again.',
                str_contains($path, 'billing') || str_contains($path, 'invoice') || str_contains($path, 'payment') => 'Unable to complete this billing operation. Please verify the invoice item details and try again.',
                default => 'Unable to complete the request because some required information is missing or invalid. Please try again, or contact the system administrator if the problem continues.',
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

        $exceptions->respond(fn ($response) => $response);
    })->create();
