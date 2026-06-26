<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Department\DepartmentContextResolver;
use App\Services\Department\DepartmentDashboardDataService;
use App\Services\Dashboard\DepartmentDashboardRegistry;
use App\Services\Dashboard\DepartmentDashboardResolver;
use Illuminate\Http\Request;

/**
 * Department-type dashboard. Resolves the right dashboard for the signed-in
 * user (by department type, then role) and renders the shared, data-driven
 * dashboard view. Does not replace the existing admin/doctor/staff dashboards.
 */
class DepartmentDashboardController extends Controller
{
    public function __construct(
        private DepartmentContextResolver $contextResolver,
        private DepartmentDashboardDataService $dashboardData,
        private DepartmentDashboardResolver $resolver,
        private DepartmentDashboardRegistry $registry,
    ) {}

    public function index(Request $request)
    {
        $context = $this->contextResolver->resolve($request->user(), $request);
        $key = $context->dashboard_key;
        $resolvedKey = $this->resolver->resolveKey($request->user());

        $data = $this->dashboardData->build($context);
        $data['context'] = $context;
        $data['theme'] = $context->theme;
        $data['dashboard'] = [
            'key' => $key,
            'title' => $this->resolver->labelFor($key),
            'subtitle' => $context->department
                ? __('dashboards.department.scoped_to_department', ['department' => $context->department->name])
                : __('dashboards.department.global_preview'),
        ];
        $data['key'] = $key;
        $data['title'] = $data['dashboard']['title'];
        $data['department'] = $context->department;
        $data['resolved_key'] = $resolvedKey;
        $data['is_preview'] = $context->is_preview || $key !== $resolvedKey;

        // Drive the admin "view as" switcher from the registry (no hardcoded list).
        $data['available_dashboards'] = $context->can_preview_departments
            ? collect($this->registry->previewableKeys())
                ->mapWithKeys(fn (string $k) => [$k => $this->resolver->labelFor($k)])
                ->all()
            : [];

        return view('admin.dashboards.department.show', $data);
    }
}
