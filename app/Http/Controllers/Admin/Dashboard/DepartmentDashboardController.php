<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
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
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // Super Admins use the original hospital-wide admin dashboard by default,
        // but may still preview any department dashboard via ?as=<key>.
        // if (! $request->filled('as') && $user->hasRole('Super Admin')) {
        //     return redirect()->route('admin.dashboard');
        // }

        // Admins may preview any dashboard via ?as=<key>; everyone else gets theirs.
        $resolvedKey = $this->resolver->resolveKey($user);
        $key = $resolvedKey;
        if ($request->filled('as') && $user->hasAnyRole(['Super Admin', 'Admin'])) {
            $key = (string) $request->query('as');
        }

        $data = $this->dashboards->build($key, $user);
        $data['title'] ??= $this->resolver->labelFor($key);
        $data['resolved_key'] = $resolvedKey;
        $data['is_preview'] = $key !== $resolvedKey;

        return view('admin.dashboards.index', $data);
    }
}
