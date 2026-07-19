<?php

use App\Http\Middleware\ConvertBladeViewsToInertia;
use App\Http\Middleware\EnsureActiveDepartmentType;
use App\Http\Middleware\EnsureInpatientWorkspaceScope;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureNursingOpdScope;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ProfileDatabaseQueries;
use App\Http\Middleware\RedirectRecordsWorkspace;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\PermissionMeta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $middleware->prepend(ProfileDatabaseQueries::class);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'module' => EnsureModuleEnabled::class,
            'department.type' => EnsureActiveDepartmentType::class,
            'records.redirect' => RedirectRecordsWorkspace::class,
            'nursing.opd.scope' => EnsureNursingOpdScope::class,
            'inpatient.scope' => EnsureInpatientWorkspaceScope::class,
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
                'error_code' => $e->getCode(),
                'user_id' => optional($request->user())->getAuthIdentifier(),
                'route' => optional($request->route())->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
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

        // Dev-only aid: surface exactly which gate/permission/middleware rejected
        // the request on a 403, since Spatie's Gate::before grants Super Admin a
        // blanket bypass — a 403 they still hit means the block came from
        // something other than a permission check (a role/module/department
        // middleware, or a raw abort() in app code), and that's non-obvious from
        // the generic "Access denied" page alone. Never runs unless APP_DEBUG=true.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! config('app.debug') || $e->getStatusCode() !== 403 || $request->expectsJson()) {
                return null;
            }

            $route = $request->route();
            $routeMiddleware = collect($route?->gatherMiddleware() ?? []);

            // `can:ability` or `can:ability,model-param` — only the ability
            // segment maps to a Spatie permission name. These are the ones we
            // can offer to grant directly from the debug panel.
            $permissionRequirements = $routeMiddleware
                ->filter(fn ($m) => str_starts_with($m, 'can:'))
                ->map(function ($m) use ($request) {
                    $ability = Str::before(Str::after($m, 'can:'), ',');

                    return [
                        'name' => $ability,
                        'exists' => Permission::where('name', $ability)->exists(),
                        'description' => PermissionMeta::description($ability),
                        'risk' => PermissionMeta::risk($ability),
                        'granted' => (bool) $request->user()?->can($ability),
                    ];
                })
                ->filter(fn ($row) => $row['exists'])
                ->values();

            // role:/module:/department-scoping middleware aren't grantable the
            // same way (they gate on role membership or module state, not a
            // Spatie permission) — list them for context only.
            $otherMiddleware = $routeMiddleware
                ->filter(fn ($m) => str_starts_with($m, 'role:')
                    || str_starts_with($m, 'module:')
                    || str_starts_with($m, 'department.type')
                    || str_starts_with($m, 'records.redirect')
                    || str_starts_with($m, 'nursing.opd.scope'))
                ->values();

            $currentUserRoles = $request->user()?->roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
            ])->values() ?? collect();

            // The original AuthorizationException (if any) is chained as the
            // previous exception once Laravel maps it to an HTTP exception; its
            // stack trace is the useful one. A raw abort(403, ...) has no
            // previous exception, so fall back to the HTTP exception itself.
            $origin = $e->getPrevious() ?? $e;

            // These middleware wrap every single request, so they always sit on
            // the stack and would otherwise be misreported as "where the 403 was
            // thrown" for route-level `can:`/`role:` checks (which have no real
            // app-code frame of their own — the middleware list above is the
            // actual answer in that case).
            $alwaysOnStack = array_map(
                fn ($path) => str_replace('\\', '/', app_path($path)),
                ['Http/Middleware/SecurityHeaders.php', 'Http/Middleware/SetLocale.php', 'Http/Middleware/HandleInertiaRequests.php', 'Http/Middleware/ConvertBladeViewsToInertia.php']
            );

            $frame = collect($origin->getTrace())->first(function ($f) use ($alwaysOnStack) {
                if (! isset($f['file'])) {
                    return false;
                }

                $file = str_replace('\\', '/', $f['file']);

                return ! str_contains($file, '/vendor/') && ! in_array($file, $alwaysOnStack, true);
            });

            return response()->view('errors.403', [
                'exception' => $e,
                'authDebug' => [
                    'exception_class' => $origin::class,
                    'message' => $origin->getMessage(),
                    'route_name' => $route?->getName(),
                    'controller_action' => $route?->getActionName(),
                    'permission_requirements' => $permissionRequirements,
                    'other_middleware' => $otherMiddleware,
                    'current_user_roles' => $currentUserRoles,
                    'thrown_at' => $frame ? $frame['file'].':'.($frame['line'] ?? '?') : null,
                    'thrown_in' => $frame ? trim(($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '')) : null,
                ],
            ], 403);
        });

        $exceptions->respond(fn ($response) => $response);
    })->create();
