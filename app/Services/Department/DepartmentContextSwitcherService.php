<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DepartmentContextSwitcherService
{
    public const SESSION_KEY = 'current_department_id';

    /** @var array<int|string, Collection<int, Department>> */
    private array $availableByUser = [];

    /** @var array<int|string, Department|null> */
    private array $primaryByUser = [];

    /** @var array<string, Department|null> */
    private array $currentByUser = [];

    /** @var array<int|string, bool> */
    private array $globalContextByUser = [];

    /**
     * @return Collection<int, Department>
     */
    public function availableDepartments(User $user): Collection
    {
        $key = $this->userKey($user);
        if (array_key_exists($key, $this->availableByUser)) {
            return $this->availableByUser[$key];
        }

        $user->loadMissing(['department', 'departments']);

        if ($this->canUseGlobalContext($user)) {
            return $this->availableByUser[$key] = Department::query()
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

        return $this->availableByUser[$key] = $assigned->unique('id')->sortBy('name')->values();
    }

    public function primaryDepartment(User $user): ?Department
    {
        $key = $this->userKey($user);
        if (array_key_exists($key, $this->primaryByUser)) {
            return $this->primaryByUser[$key];
        }

        $user->loadMissing(['department', 'departments']);

        $primary = $user->departments
            ->first(fn (Department $department) => (bool) ($department->pivot?->is_primary ?? false) && $this->pivotIsCurrentlyActive($department));

        return $this->primaryByUser[$key] = $primary ?? $user->department;
    }

    public function currentDepartment(User $user, ?Request $request = null): ?Department
    {
        $session = ($request && $request->hasSession()) ? $request->session() : session();
        $sessionDepartmentId = $session->get(self::SESSION_KEY);
        $key = $this->currentKey($user, $sessionDepartmentId);
        if (array_key_exists($key, $this->currentByUser)) {
            return $this->currentByUser[$key];
        }

        $available = $this->availableDepartments($user);

        if ($sessionDepartmentId) {
            $department = $available->firstWhere('id', (int) $sessionDepartmentId);

            if ($department) {
                return $this->currentByUser[$key] = $department;
            }

            $session->forget(self::SESSION_KEY);
        }

        return $this->currentByUser[$key] = $this->primaryDepartment($user);
    }

    public function switch(User $user, int $departmentId, Request $request): ?Department
    {
        $department = $this->availableDepartments($user)->firstWhere('id', $departmentId);

        if (! $department) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $request->session()->put(self::SESSION_KEY, $department->id);

        $this->currentByUser[$this->currentKey($user, $department->id)] = $department;

        return $department;
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        $this->currentByUser = [];
    }

    public function isSwitched(User $user, ?Department $current): bool
    {
        $primary = $this->primaryDepartment($user);

        return $current && $primary && (int) $current->id !== (int) $primary->id;
    }

    public function canUseGlobalContext(User $user): bool
    {
        $key = $this->userKey($user);

        return $this->globalContextByUser[$key] ??= $user->hasAnyRole(['Super Admin', 'Admin'])
            || $user->can('dashboards.department.global_preview');
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

    private function userKey(User $user): int|string
    {
        return $user->getAuthIdentifier() ?? 'object:'.spl_object_id($user);
    }

    private function currentKey(User $user, mixed $sessionDepartmentId): string
    {
        return $this->userKey($user).':'.($sessionDepartmentId ?: 'primary');
    }
}
