<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Department\DepartmentContextResolver;
use App\Services\Department\DepartmentDashboardDataService;
use App\Services\Dashboard\DepartmentDashboardRegistry;
use App\Services\Dashboard\DepartmentDashboardResolver;
use App\Services\DepartmentMenuProfileService;
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
        private DepartmentMenuProfileService $menuProfiles,
    ) {}

    public function index(Request $request)
    {
        $context = $this->contextResolver->resolve($request->user(), $request);
        $key = $context->dashboard_key;
        $resolvedKey = $this->resolver->resolveKey($request->user());

        $data = $this->dashboardData->build($context);
        $layout = $data['layout_profile'];

        // Identity / personality / scope (the Phase 8.1 core rule):
        //   type  → dashboard name + layout family    name  → identity    id → scope
        $type = $context->current_department_type ?? $context->department_type;
        $department = $context->current_department ?? $context->department;
        $departmentName = $department?->name;
        $typeLabel = $context->department_type_label;
        $dashboardName = __($layout['name_key']);
        $menuHeading = $this->menuProfiles->headingForType($type, $departmentName);

        // Distinguish an admin's global preview from a user who simply has no
        // department (both are "global context" in the resolver, but mean different
        // things to the person looking at the screen).
        $scopeMessage = match (true) {
            $context->is_global_context && $context->can_use_global_context => __('departments.dashboard.global_preview_mode'),
            $departmentName !== null && $context->is_preview => __('departments.dashboard.viewing_as_department', ['department' => $departmentName]),
            $departmentName !== null => __('departments.dashboard.viewing_department_data_only', ['department' => $departmentName]),
            default => __('departments.dashboard.no_department_assigned_dashboard'),
        };

        $subtitle = match (true) {
            $departmentName !== null => __('departments.dashboard.subtitle', ['department' => $departmentName, 'type' => $typeLabel]),
            $context->can_use_global_context => __('departments.dashboard.global_preview_mode'),
            default => __('departments.dashboard.no_department_assigned_dashboard'),
        };

        $data['context'] = $context;
        $data['theme'] = $context->theme;
        $data['layout_family'] = $layout['layout_family'];
        $data['dashboard_name'] = $dashboardName;
        $data['menu_heading'] = $menuHeading;
        $data['dashboard'] = [
            'key' => $key,
            'title' => $dashboardName,
            'subtitle' => $subtitle,
            'menu_heading' => $menuHeading,
            'welcome' => __('departments.dashboard.welcome_user', ['name' => $context->user->first_name ?? $context->user->name ?? '']),
            'scope_message' => $scopeMessage,
        ];
        $data['key'] = $key;
        $data['title'] = $dashboardName;
        $data['department'] = $context->department;
        $data['resolved_key'] = $resolvedKey;
        $data['is_preview'] = $context->is_preview || $key !== $resolvedKey;

        // Drive the admin "view as" switcher from the registry (no hardcoded list).
        $data['available_dashboards'] = $context->can_preview_departments
            ? collect($this->registry->previewableKeys())
                ->mapWithKeys(fn (string $k) => [$k => $this->resolver->labelFor($k)])
                ->all()
            : [];

        return view($this->viewForDashboard($key), $data);
    }

    private function viewForDashboard(string $key): string
    {
        $view = 'admin.dashboards.department.types.'.$key;

        return view()->exists($view)
            ? $view
            : 'admin.dashboards.department.types.generic';
    }
}
