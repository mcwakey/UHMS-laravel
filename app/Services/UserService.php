<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(private RolePermissionAuditService $audit) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['department', 'designation', 'roles']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (!empty($filters['department_id'] ?? $filters['department'] ?? null)) {
            $query->where('department_id', $filters['department_id'] ?? $filters['department']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): User
    {
        if (isset($data['avatar'])) {
            $data['avatar'] = $data['avatar']->store('avatars', 'public');
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
            $this->audit->userRolesUpdated($user, [], $user->getRoleNames()->all());
        }

        if (isset($data['specialties'])) {
            $user->specialties()->sync($data['specialties']);
        }

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['avatar'])) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $data['avatar']->store('avatars', 'public');
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['role'])) {
            $before = $user->roles->pluck('name')->all();
            $user->syncRoles([$data['role']]);
            $this->audit->userRolesUpdated($user, $before, $user->fresh()->getRoleNames()->all());
        }

        if (array_key_exists('specialties', $data)) {
            $user->specialties()->sync($data['specialties'] ?? []);
        }

        return $user;
    }

    public function toggleStatus(User $user): User
    {
        $user->status = $user->status === UserStatus::ACTIVE
            ? UserStatus::INACTIVE
            : UserStatus::ACTIVE;

        $user->save();

        return $user;
    }
}
