<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request)
    {
        $users = $this->userService->list($request->all());
        $roles = Role::all();
        $departments = Department::active()->get();

        return view('users.index', compact('users', 'roles', 'departments'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::active()->get();

        return view('users.create', compact('roles', 'departments'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load(['department', 'designation', 'roles']);
        $roles = Role::all();
        $departments = Department::active()->get();

        return view('users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->userService->toggleStatus($user);

        return redirect()->back()
            ->with('success', 'User status updated.');
    }
}
