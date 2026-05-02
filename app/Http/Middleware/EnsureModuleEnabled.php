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
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        if ($this->modules->disabled($slug)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "The {$slug} module is currently disabled.",
                ], 503);
            }

            abort(404, "The {$slug} module is currently disabled.");
        }

        return $next($request);
    }
}
