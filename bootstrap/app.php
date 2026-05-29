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

        $exceptions->respond(function ($response, \Throwable $e, \Illuminate\Http\Request $request) use ($redirectInertiaToLogin) {
            if ($request->headers->has('X-Inertia') && in_array($response->getStatusCode(), [401, 419], true)) {
                return $redirectInertiaToLogin($request);
            }

            return $response;
        });
    })->create();
