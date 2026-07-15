<?php

namespace App\Http\Middleware;

use App\Services\WorkspaceRouteResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectRecordsWorkspace
{
    public function __construct(
        private WorkspaceRouteResolver $workspaceRoutes,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name
            && $this->workspaceRoutes->isRecords()
            && in_array($request->method(), ['GET', 'HEAD'], true)
            && ! $request->expectsJson()
            && ! $request->ajax()
            && ! $request->hasValidSignature()
        ) {
            $target = $this->workspaceRoutes->routeName($name);

            if ($target !== $name) {
                $url = route($target, array_merge($route->parameters(), $request->query()));

                return redirect()->to($url);
            }
        }

        return $next($request);
    }
}
