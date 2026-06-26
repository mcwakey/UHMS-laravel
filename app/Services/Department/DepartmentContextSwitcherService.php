<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DepartmentContextSwitcherService
{
    public const SESSION_KEY = 'current_department_id';

    /**
     * @return Collection<int, Department>
     */
    public function availableDepartments(User $user): Collection
    {
        $user->loadMissing(['department', 'departments']);

        if ($this->canUseGlobalContext($user)) {
            return Department::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->values();
        }

        $assigned = $user->departments
            ->filter(fn (Department $department) => $this->pivotIsCurrentlyActive($department))
            ->values();

        if ($user->department && ! $assigned->contains('id', $user->department->id)) {
            $assigned->prepend($user->department);
        }

        return $assigned->unique('id')->sortBy('name')->values();
    }

    public function primaryDepartment(User $user): ?Department
    {
        $user->loadMissing(['department', 'departments']);

        $primary = $user->departments
            ->first(fn (Department $department) => (bool) ($department->pivot?->is_primary ?? false) && $this->pivotIsCurrentlyActive($department));

        return $primary ?? $user->department;
    }

    public function currentDepartment(User $user, ?Request $request = null): ?Department
    {
        $available = $this->availableDepartments($user);
        $session = ($request && $request->hasSession()) ? $request->session() : session();
        $sessionDepartmentId = $session->get(self::SESSION_KEY);

        if ($sessionDepartmentId) {
            $department = $available->firstWhere('id', (int) $sessionDepartmentId);

            if ($department) {
                return $department;
            }

            $session->forget(self::SESSION_KEY);
        }

        return $this->primaryDepartment($user);
    }

    public function switch(User $user, int $departmentId, Request $request): ?Department
    {
        $department = $this->availableDepartments($user)->firstWhere('id', $departmentId);

        if (! $department) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $request->session()->put(self::SESSION_KEY, $department->id);

        return $department;
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function isSwitched(User $user, ?Department $current): bool
    {
        $primary = $this->primaryDepartment($user);

        return $current && $primary && (int) $current->id !== (int) $primary->id;
    }

    public function canUseGlobalContext(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('dashboards.department.global_preview');
    }

    public function canSwitch(User $user): bool
    {
        return ($user->can('departments.context.switch') || $this->canUseGlobalContext($user))
            && $this->availableDepartments($user)->count() > 1;
    }

    private function pivotIsCurrentlyActive(Department $department): bool
    {
        $startsAt = $department->pivot?->starts_at;
        $endsAt = $department->pivot?->ends_at;
        $now = now();

        return (! $startsAt || $now->greaterThanOrEqualTo($startsAt))
            && (! $endsAt || $now->lessThanOrEqualTo($endsAt));
    }
}
