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

        $message = match (true) {
            in_array(DepartmentType::NURSING->value, $allowedTypes, true) => __('nursing.unauthorized'),
            in_array(DepartmentType::EMERGENCY->value, $allowedTypes, true) => __('emergency.unauthorized'),
            in_array(DepartmentType::INPATIENT->value, $allowedTypes, true) => __('inpatient.unauthorized'),
            in_array(DepartmentType::INVESTIGATION->value, $allowedTypes, true) => __('investigations.unauthorized'),
            in_array(DepartmentType::PHARMACY->value, $allowedTypes, true) => __('pharmacy.unauthorized'),
            in_array(DepartmentType::STORES->value, $allowedTypes, true) => __('stores.unauthorized'),
            in_array(DepartmentType::FINANCE->value, $allowedTypes, true) => __('finance.unauthorized'),
            in_array(DepartmentType::MATERNITY->value, $allowedTypes, true) => __('maternity.workspace.unauthorized'),
            in_array(DepartmentType::ADMINISTRATIVE->value, $allowedTypes, true) => __('administrative.unauthorized'),
            default => __('records.unauthorized'),
        };

        abort_unless($department && in_array($type, $allowedTypes, true), 403, $message);

        return $next($request);
    }
}
