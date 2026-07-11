<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\CallCategory;
use App\Enums\FrontDesk\CallDirection;
use App\Enums\FrontDesk\CallFollowUpStatus;
use App\Enums\FrontDesk\CallOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrontDesk\StoreCallLogRequest;
use App\Http\Requests\FrontDesk\UpdateCallLogRequest;
use App\Models\Department;
use App\Models\FrontDeskCallLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\FrontDesk\CallLogService;
use Illuminate\Http\Request;

class CallLogController extends Controller
{
    public function __construct(private CallLogService $service) {}

    public function index(Request $request)
    {
        $logs = FrontDeskCallLog::query()
            ->with(['department', 'patient', 'handledBy', 'assignedFollowUpUser'])
            ->search($request->string('search'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->direction($request->input('direction'))
            ->category($request->input('category'))
            ->outcome($request->input('outcome'))
            ->department($request->integer('department_id') ?: null)
            ->followUpStatus($request->input('follow_up_status'))
            ->when($request->integer('assigned_user') ?: null, fn ($q, $id) => $q->assignedTo($id))
            ->when($request->boolean('due_today'), fn ($q) => $q->dueTodayCallback())
            ->when($request->boolean('overdue'), fn ($q) => $q->overdueCallback())
            ->when($request->boolean('transferred'), fn ($q) => $q->transferred())
            ->when($request->filled('follow_up_required'), fn ($q) => $q->where('follow_up_required', $request->boolean('follow_up_required')))
            ->latest('started_at')
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.calls.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'direction', 'category', 'outcome', 'department_id', 'follow_up_required', 'follow_up_status', 'assigned_user', 'due_today', 'overdue', 'transferred']),
            'directions' => CallDirection::cases(),
            'categories' => CallCategory::cases(),
            'outcomes' => CallOutcome::cases(),
            'followUpStatuses' => CallFollowUpStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Callback queue — pending follow-ups with due-date-aware ordering. */
    public function followUps(Request $request)
    {
        $logs = FrontDeskCallLog::query()
            ->pendingCallback()
            ->with(['department', 'patient', 'handledBy', 'assignedFollowUpUser'])
            ->when($request->boolean('overdue'), fn ($q) => $q->overdueCallback())
            ->when($request->boolean('due_today'), fn ($q) => $q->dueTodayCallback())
            ->when($request->boolean('mine'), fn ($q) => $q->assignedTo($request->user()->id))
            ->when($request->integer('assigned_user') ?: null, fn ($q, $id) => $q->assignedTo($id))
            ->orderByRaw('follow_up_due_at is null, follow_up_due_at asc')
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.calls.follow-ups', [
            'logs' => $logs,
            'filters' => $request->only(['overdue', 'due_today', 'mine', 'assigned_user']),
            'users' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function create()
    {
        return view('admin.front-desk.calls.create', $this->formData());
    }

    public function store(StoreCallLogRequest $request)
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.front-desk.calls.index')
            ->with('success', __('front_desk.flash.call_created'));
    }

    public function show(FrontDeskCallLog $call)
    {
        $call->load(['department', 'patient', 'visit', 'handledBy', 'assignedFollowUpUser', 'followUpCompletedBy', 'followUpCancelledBy', 'transferDepartment', 'transferredToUser']);

        return view('admin.front-desk.calls.show', array_merge($this->formData(), ['log' => $call]));
    }

    public function edit(FrontDeskCallLog $call)
    {
        return view('admin.front-desk.calls.edit', array_merge($this->formData(), ['log' => $call]));
    }

    public function update(UpdateCallLogRequest $request, FrontDeskCallLog $call)
    {
        $this->service->update($call, $request->validated(), $request->user());

        return redirect()
            ->route('admin.front-desk.calls.show', $call)
            ->with('success', __('front_desk.flash.call_updated'));
    }

    public function assignFollowUp(Request $request, FrontDeskCallLog $call)
    {
        $data = $request->validate([
            'assigned_follow_up_user_id' => ['nullable', 'exists:users,id'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->assignFollowUp($call, $data, $request->user());

        return back()->with('success', __('front_desk.flash.call_followup_assigned'));
    }

    public function completeFollowUp(Request $request, FrontDeskCallLog $call)
    {
        $data = $request->validate(['completion_note' => ['nullable', 'string', 'max:1000']]);

        $this->service->completeFollowUp($call, $request->user(), $data['completion_note'] ?? null);

        return back()->with('success', __('front_desk.flash.call_followup_completed'));
    }

    public function cancelFollowUp(Request $request, FrontDeskCallLog $call)
    {
        $data = $request->validate(['cancellation_reason' => ['nullable', 'string', 'max:1000']]);

        $this->service->cancelFollowUp($call, $request->user(), $data['cancellation_reason'] ?? null);

        return back()->with('success', __('front_desk.flash.call_followup_cancelled'));
    }

    public function transfer(Request $request, FrontDeskCallLog $call)
    {
        $data = $request->validate([
            'transfer_department_id' => ['nullable', 'exists:departments,id'],
            'transferred_to_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $this->service->transferCall($call, $data, $request->user());

        return back()->with('success', __('front_desk.flash.call_transferred'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'directions' => CallDirection::cases(),
            'categories' => CallCategory::cases(),
            'outcomes' => CallOutcome::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'patients' => Patient::orderByDesc('id')->limit(50)->get(['id', 'patient_number', 'first_name', 'last_name']),
            'users' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
