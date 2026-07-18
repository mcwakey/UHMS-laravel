<?php

namespace App\Http\Middleware;

use App\Services\InpatientWorkspaceScope;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInpatientWorkspaceScope
{
    public function __construct(private InpatientWorkspaceScope $scope) {}

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->route()?->parameters() ?? [] as $resource) {
            if ($resource instanceof Model && ! $this->scope->contains($resource)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
