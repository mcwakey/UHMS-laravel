<?php

namespace App\Http\Middleware;

use App\Models\ServiceRendering;
use App\Models\Visit;
use App\Services\Department\DepartmentContextSwitcherService;
use App\Services\Nursing\NursingOpdService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNursingOpdScope
{
    public function __construct(
        private DepartmentContextSwitcherService $departments,
        private NursingOpdService $opd,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->route('visit') ?? $request->input('visit_id');

        if ($value !== null) {
            $visit = $value instanceof Visit ? $value : Visit::findOrFail($value);
            $department = $this->departments->currentDepartment($request->user(), $request);
            abort_unless($department, 403, __('nursing.unauthorized'));
            $this->opd->ensureAccessible($visit, $department);
        }

        $rendering = $request->route('serviceRendering');
        if ($rendering !== null) {
            $rendering = $rendering instanceof ServiceRendering ? $rendering : ServiceRendering::findOrFail($rendering);
            $department = $this->departments->currentDepartment($request->user(), $request);
            abort_unless($department, 403, __('nursing.unauthorized'));
            abort_unless((int) $rendering->department_id === (int) $department->id && ! $rendering->admission_id && ! $rendering->emergency_case_id, 404);
            $this->opd->ensureAccessible($rendering->visit()->firstOrFail(), $department);
        }

        return $next($request);
    }
}
