<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\HRService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private HRService $hrService) {}

    public function index(Request $request)
    {
        $employees = $this->hrService->listEmployees($request->only('search', 'department_id', 'status'));
        $departments = Department::active()->orderBy('name')->get();
        $statuses = EmployeeStatus::cases();

        return view('hr.employees.index', compact('employees', 'departments', 'statuses'));
    }

    public function create()
    {
        $departments = Department::active()->orderBy('name')->get();
        $users = User::whereDoesntHave('employee')->orderBy('first_name')->orderBy('last_name')->get();
        $genders = Gender::cases();
        $statuses = EmployeeStatus::cases();

        return view('hr.employees.create', compact('departments', 'users', 'genders', 'statuses'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->hrService->createEmployee($request->validated());
        return redirect()->route('admin.hr.employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['department', 'user']);
        $leaveRequests = $employee->leaveRequests()->latest()->take(5)->get();
        $employee->setRelation('leaveRequests', $leaveRequests);
        $leaveBalance = $this->hrService->getLeaveBalance($employee->id);

        return view('hr.employees.show', compact('employee', 'leaveBalance'));
    }

    public function edit(Employee $employee)
    {
        $departments = Department::active()->orderBy('name')->get();
        $users = User::where(function ($q) use ($employee) {
            $q->whereDoesntHave('employee')->orWhere('id', $employee->user_id);
        })->orderBy('name')->get();
        $genders = Gender::cases();
        $statuses = EmployeeStatus::cases();

        return view('hr.employees.edit', compact('employee', 'departments', 'users', 'genders', 'statuses'));
    }

    public function update(StoreEmployeeRequest $request, Employee $employee)
    {
        $this->hrService->updateEmployee($employee, $request->validated());
        return redirect()->route('admin.hr.employees.show', $employee)->with('success', 'Employee updated successfully.');
    }
}
