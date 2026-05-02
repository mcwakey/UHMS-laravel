<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
