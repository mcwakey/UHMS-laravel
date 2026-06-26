<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\LogModule;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDepartmentAssignmentController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(User $user)
    {
        $user->load(['department', 'departments']);
        $departments = Department::active()->orderBy('name')->get();

        return view('users.departments', compact('user', 'departments'));
    }

    public function store(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 403);

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'role_context' => ['nullable', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $existing = DB::table('department_user')
            ->where('user_id', $user->id)
            ->where('department_id', $validated['department_id'])
            ->first();

        if ($existing && $this->assignmentIsAvailable($existing)) {
            return back()->withErrors(['department_id' => __('users.duplicate_active_assignment')])->withInput();
        }

        DB::transaction(function () use ($user, $validated, $existing) {
            if ($validated['is_primary'] ?? false) {
                DB::table('department_user')->where('user_id', $user->id)->update(['is_primary' => false]);
                $user->forceFill(['department_id' => $validated['department_id']])->save();
            }

            $payload = [
                'role_context' => $validated['role_context'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'is_primary' => (bool) ($validated['is_primary'] ?? false),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('department_user')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('department_user')->insert(array_merge($payload, [
                    'user_id' => $user->id,
                    'department_id' => $validated['department_id'],
                    'created_at' => now(),
                ]));
            }
        });

        $this->activityLog->log(LogModule::USERS, 'USER_DEPARTMENT_ASSIGNED', [
            'target_user_id' => $user->id,
            'department_id' => $validated['department_id'],
            'metadata' => ['is_primary' => (bool) ($validated['is_primary'] ?? false)],
        ], $user);

        return back()->with('success', __('users.department_assignment_saved'));
    }

    public function setPrimary(Request $request, User $user, Department $department)
    {
        abort_if($request->user()->is($user), 403);

        $hasAssignment = DB::table('department_user')
            ->where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->exists();

        abort_unless($hasAssignment || (int) $user->department_id === (int) $department->id, 404);

        DB::transaction(function () use ($user, $department, $hasAssignment) {
            DB::table('department_user')->where('user_id', $user->id)->update(['is_primary' => false]);

            if ($hasAssignment) {
                DB::table('department_user')
                    ->where('user_id', $user->id)
                    ->where('department_id', $department->id)
                    ->update(['is_primary' => true, 'updated_at' => now()]);
            } else {
                DB::table('department_user')->insert([
                    'user_id' => $user->id,
                    'department_id' => $department->id,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $user->forceFill(['department_id' => $department->id])->save();
        });

        $this->activityLog->log(LogModule::USERS, 'USER_DEPARTMENT_PRIMARY_SET', [
            'target_user_id' => $user->id,
            'department_id' => $department->id,
        ], $user);

        return back()->with('success', __('users.primary_department_updated'));
    }

    public function destroy(Request $request, User $user, Department $department)
    {
        abort_if($request->user()->is($user), 403);

        DB::table('department_user')
            ->where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->delete();

        if ((int) $user->department_id === (int) $department->id) {
            $fallback = DB::table('department_user')
                ->where('user_id', $user->id)
                ->orderByDesc('is_primary')
                ->value('department_id');

            $user->forceFill(['department_id' => $fallback])->save();
        }

        $this->activityLog->log(LogModule::USERS, 'USER_DEPARTMENT_REMOVED', [
            'target_user_id' => $user->id,
            'department_id' => $department->id,
        ], $user);

        return back()->with('success', __('users.department_assignment_removed'));
    }

    private function assignmentIsAvailable(object $assignment): bool
    {
        $now = now();

        return (! $assignment->starts_at || $now->greaterThanOrEqualTo($assignment->starts_at))
            && (! $assignment->ends_at || $now->lessThanOrEqualTo($assignment->ends_at));
    }
}
