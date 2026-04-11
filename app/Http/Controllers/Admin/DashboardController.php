<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $totalDepartments = Department::where('status', 'active')->count();
        $totalRoles = Role::count();
        $recentUsers = User::with(['roles', 'department'])->latest()->take(5)->get();

        return view('dashboard.admin', compact(
            'totalUsers', 'activeUsers', 'totalDepartments', 'totalRoles', 'recentUsers'
        ));
    }
}
