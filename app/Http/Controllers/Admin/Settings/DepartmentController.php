<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\ResultType;
use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\StockLocationSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function __construct(
        private StockLocationSyncService $stockLocationSync,
        private ActivityLogService $activity,
    ) {}

    public function index(Request $request)
    {
        $departments = Department::withCount(['users', 'designations'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        $departmentTypes = DepartmentType::cases();
        $resultTypes     = ResultType::selectableCases();
        // Active staff eligible to be a supervisor / escalation contact (Phase 9.7).
        $supervisorCandidates = User::active()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('departments.index', compact('departments', 'departmentTypes', 'resultTypes', 'supervisorCandidates'));
    }

    /** Active-user existence rule for supervisor/escalation fields. */
    private function supervisorRules(): array
    {
        return ['nullable', 'integer', Rule::exists('users', 'id')->where('status', UserStatus::ACTIVE->value)];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => 'required|string|max:10|unique:departments,code',
            'type'             => ['nullable', Rule::enum(DepartmentType::class)],
            'result_type'      => ['required', Rule::enum(ResultType::class)],
            'is_stock_managed' => 'nullable|boolean',
            'description'      => 'nullable|string|max:500',
            'status'           => 'required|in:active,inactive',
            'supervisor_user_id' => $this->supervisorRules(),
            'escalation_user_id' => $this->supervisorRules(),
        ]);

        $validated['is_stock_managed'] = $request->boolean('is_stock_managed');

        $department = Department::create($validated);

        // Auto-create a stock location when the department manages stock.
        $this->stockLocationSync->ensureDepartmentLocation($department);

        return redirect()->route('admin.departments.index')
            ->with('success', __('messages.departments.created'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'code'             => "required|string|max:10|unique:departments,code,{$department->id}",
            'type'             => ['nullable', Rule::enum(DepartmentType::class)],
            'result_type'      => ['required', Rule::enum(ResultType::class)],
            'is_stock_managed' => 'nullable|boolean',
            'description'      => 'nullable|string|max:500',
            'status'           => 'required|in:active,inactive',
            'supervisor_user_id' => $this->supervisorRules(),
            'escalation_user_id' => $this->supervisorRules(),
        ]);

        $validated['is_stock_managed'] = $request->boolean('is_stock_managed');

        $oldSupervisor = $department->supervisor_user_id;
        $oldEscalation = $department->escalation_user_id;

        $department->update($validated);

        // Audit escalation-routing changes (Phase 9.7).
        if ($oldSupervisor !== $department->supervisor_user_id || $oldEscalation !== $department->escalation_user_id) {
            $this->activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_DEPARTMENT_SUPERVISOR_CHANGED', [
                'severity' => LogSeverity::INFO,
                'department_id' => $department->id,
                'old_supervisor_user_id' => $oldSupervisor,
                'new_supervisor_user_id' => $department->supervisor_user_id,
                'old_escalation_user_id' => $oldEscalation,
                'new_escalation_user_id' => $department->escalation_user_id,
            ], $department, 'Department escalation routing updated: '.$department->name);
        }

        // Keep the department's stock location in sync when stock management is on.
        $this->stockLocationSync->ensureDepartmentLocation($department);

        return redirect()->route('admin.departments.index')
            ->with('success', __('messages.departments.updated'));
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return redirect()->back()
                ->with('error', __('messages.departments.cannot_delete'));
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', __('messages.departments.deleted'));
    }
}
