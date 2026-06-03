<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(protected ModuleService $modules) {}

    /**
     * Usage: Route::middleware('module:pharmacy')->group(...)
     *
     * Bypassed for users with the `modules.override_disabled` permission so
     * Super Admins can still access screens during incident response.
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        if ($this->modules->disabled($slug)) {
            $user = $request->user();
            if ($user && method_exists($user, 'can') && $user->can('modules.override_disabled')) {
                return $next($request);
            }

            $module = $this->modules->find($slug);
            $name = $module?->name ?: \Illuminate\Support\Str::headline($slug);

            // 403 Forbidden — the module exists but access is not permitted while
            // it is disabled. A friendly page (web) / clean JSON (API), never a
            // raw exception or stack trace.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "The {$name} module is currently disabled.",
                ], 403);
            }

            return response()->view('errors.module-disabled', [
                'moduleName' => $name,
                'moduleDescription' => $module?->description,
            ], 403);
        }

        return $next($request);
    }
}
