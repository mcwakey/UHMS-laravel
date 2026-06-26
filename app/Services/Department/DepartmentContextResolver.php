<?php

namespace App\Services\Department;

use App\Enums\DepartmentType;
use App\Models\User;
use App\Services\Dashboard\DepartmentDashboardRegistry;
use App\Services\Dashboard\DepartmentDashboardResolver;
use App\Services\DepartmentMenuProfileService;
use Illuminate\Http\Request;

class DepartmentContextResolver
{
    public function __construct(
        private DepartmentDashboardRegistry $dashboardRegistry,
        private DepartmentDashboardResolver $dashboardResolver,
        private DepartmentMenuProfileService $menuProfiles,
        private DepartmentDashboardThemeRegistry $themes,
    ) {}

    public function resolve(User $user, ?Request $request = null): DepartmentDashboardContext
    {
        $user->loadMissing('department');

        $department = $user->department;
        $type = $department?->type instanceof DepartmentType ? $department->type : null;
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin']);
        $canPreview = $isAdmin;
        $globalRequested = $isAdmin && $request?->boolean('global');
        $requestedDashboard = (string) ($request?->query('as', '') ?? '');

        $isGlobal = $globalRequested || ! $department;
        $dashboardKey = $isGlobal
            ? $this->dashboardResolver->resolveKey($user)
            : ($this->dashboardRegistry->keyForType($type) ?? $this->dashboardResolver->resolveKey($user));

        $isPreview = false;
        if ($canPreview && $requestedDashboard !== '' && $this->dashboardRegistry->isPreviewable($requestedDashboard)) {
            $dashboardKey = $requestedDashboard;
            $isPreview = true;
        }

        $label = $type?->translatedLabel() ?? __('dashboards.department.generic_type');
        $menuProfileKey = $this->menuProfiles->profileKeyForType($type);
        $theme = $this->themes->for($type, $dashboardKey);

        return new DepartmentDashboardContext(
            user: $user,
            department: $isGlobal ? null : $department,
            department_id: $isGlobal ? null : $department?->id,
            department_type: $isGlobal ? null : $type,
            department_type_label: $isGlobal ? __('dashboards.department.global_context') : $label,
            dashboard_key: $dashboardKey,
            menu_profile_key: $menuProfileKey,
            theme: $theme,
            is_global_context: $isGlobal,
            can_preview_departments: $canPreview,
            is_preview: $isPreview,
            requested_dashboard_key: $requestedDashboard !== '' ? $requestedDashboard : null,
        );
    }
}
