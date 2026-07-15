<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\RolePermissionAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dev-only shortcut wired to the 403 debug panel (see bootstrap/app.php and
 * resources/views/errors/403.blade.php). Lets the signed-in user grant
 * themselves — or one of their own roles — a permission they were just
 * blocked on, without leaving the error page.
 *
 * Hard-gated behind APP_DEBUG in this controller (not just route
 * registration/UI visibility) so it can never be reachable in production.
 * Every grant still goes through the normal audit funnel and is scoped to
 * the signed-in user's own account/roles — it cannot target another user.
 */
class DebugPermissionGrantController extends Controller
{
    public function __construct(private RolePermissionAuditService $audit) {}

    public function store(Request $request)
    {
        abort_unless(config('app.debug'), 404);

        $user = $request->user();
        abort_unless($user, 404);

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
            'target' => 'required|in:role,user',
            'role_id' => 'required_if:target,role|nullable|integer',
            'redirect_to' => 'nullable|string',
        ]);

        $permission = $validated['permission'];

        if ($validated['target'] === 'role') {
            // Scoped to roles the requesting user already holds — this cannot
            // be used to modify an unrelated role by guessing its id.
            $role = $user->roles()->whereKey($validated['role_id'])->first();
            abort_unless($role, 404);

            $before = $role->permissions->pluck('name')->all();
            $role->givePermissionTo($permission);
            $this->audit->rolePermissionsUpdated($role, $before, $role->fresh()->permissions->pluck('name')->all());
        } else {
            $before = $user->permissions->pluck('name')->all();
            $user->givePermissionTo($permission);
            $this->audit->userPermissionsUpdated(
                $user,
                $before,
                $user->fresh()->permissions->pluck('name')->all(),
                '[DEV] Self-granted via 403 debug panel (APP_DEBUG)',
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $redirectTo = $validated['redirect_to'] ?? null;
        $safeRedirect = ($redirectTo && Str::startsWith($redirectTo, url('/')))
            ? $redirectTo
            : route('admin.dashboard');

        return redirect($safeRedirect)->with('success', "[DEV] Granted '{$permission}'. Retry your action now.");
    }
}
