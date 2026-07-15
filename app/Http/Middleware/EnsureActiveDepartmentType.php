<?php

namespace App\Http\Middleware;

use App\Enums\DepartmentType;
use App\Services\Department\DepartmentContextSwitcherService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveDepartmentType
{
    public function __construct(
        private DepartmentContextSwitcherService $departments,
    ) {}

    public function handle(Request $request, Closure $next, string ...$allowedTypes): Response
    {
        $department = $request->user()
            ? $this->departments->currentDepartment($request->user(), $request)
            : null;
        $type = $department?->type instanceof DepartmentType
            ? $department->type->value
            : (string) ($department?->type ?? '');

        $message = in_array(DepartmentType::NURSING->value, $allowedTypes, true)
            ? __('nursing.unauthorized')
            : __('records.unauthorized');

        abort_unless($department && in_array($type, $allowedTypes, true), 403, $message);

        return $next($request);
    }
}
