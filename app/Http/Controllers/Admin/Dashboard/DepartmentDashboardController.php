<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DepartmentDashboardRegistry;
use App\Services\Dashboard\DepartmentDashboardResolver;
use App\Services\Dashboard\DepartmentDashboardService;
use Illuminate\Http\Request;

/**
 * Department-type dashboard. Resolves the right dashboard for the signed-in
 * user (by department type, then role) and renders the shared, data-driven
 * dashboard view. Does not replace the existing admin/doctor/staff dashboards.
 */
class DepartmentDashboardController extends Controller
{
    public function __construct(
        private DepartmentDashboardResolver $resolver,
        private DepartmentDashboardService $dashboards,
        private DepartmentDashboardRegistry $registry,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin']);

        // Admins may preview any dashboard via ?as=<key>; everyone else gets theirs.
        $resolvedKey = $this->resolver->resolveKey($user);
        $key = $resolvedKey;
        $requested = (string) $request->query('as', '');
        if ($isAdmin && $requested !== '' && $this->registry->isPreviewable($requested)) {
            $key = $requested;
        }

        $data = $this->dashboards->build($key, $user);
        $data['key'] ??= $key;
        $data['title'] ??= $this->resolver->labelFor($key);
        $data['resolved_key'] = $resolvedKey;
        $data['is_preview'] = $key !== $resolvedKey;

        // Drive the admin "view as" switcher from the registry (no hardcoded list).
        $data['available_dashboards'] = $isAdmin
            ? collect($this->registry->previewableKeys())
                ->mapWithKeys(fn (string $k) => [$k => $this->resolver->labelFor($k)])
                ->all()
            : [];

        return view('admin.dashboards.index', $data);
    }
}
