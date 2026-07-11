<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\FrontDeskIncidentSeverity;
use App\Enums\FrontDesk\FrontDeskIncidentStatus;
use App\Enums\FrontDesk\FrontDeskIncidentType;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskCourierLog;
use App\Models\FrontDeskIncidentLog;
use App\Models\FrontDeskVisitorLog;
use App\Models\User;
use App\Services\FrontDesk\IncidentLogService;
use Illuminate\Http\Request;

class IncidentLogController extends Controller
{
    public function __construct(private IncidentLogService $service) {}

    public function index(Request $request)
    {
        $logs = FrontDeskIncidentLog::query()
            ->with(['department', 'assignedToUser', 'escalatedToUser'])
            ->search($request->string('search'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->status($request->input('status'))
            ->type($request->input('incident_type'))
            ->severity($request->input('severity'))
            ->department($request->integer('department_id') ?: null)
            ->when($request->boolean('open'), fn ($q) => $q->open())
            ->when($request->boolean('critical'), fn ($q) => $q->critical())
            ->latest('reported_at')
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.incidents.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'status', 'incident_type', 'severity', 'department_id', 'open', 'critical']),
            'types' => FrontDeskIncidentType::cases(),
            'severities' => FrontDeskIncidentSeverity::cases(),
            'statuses' => FrontDeskIncidentStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('admin.front-desk.incidents.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return redirect()->route('admin.front-desk.incidents.index')
            ->with('success', __('front_desk.flash.incident_created'));
    }

    public function show(FrontDeskIncidentLog $incident)
    {
        $incident->load(['department', 'assignedToUser', 'escalatedToUser', 'resolvedBy', 'createdBy', 'relatedVisitorLog', 'relatedCallLog', 'relatedCourierLog']);

        return view('admin.front-desk.incidents.show', array_merge($this->formData(), ['log' => $incident]));
    }

    public function edit(FrontDeskIncidentLog $incident)
    {
        return view('admin.front-desk.incidents.edit', array_merge($this->formData(), ['log' => $incident]));
    }

    public function update(Request $request, FrontDeskIncidentLog $incident)
    {
        $this->service->update($incident, $this->validated($request), $request->user());

        return redirect()->route('admin.front-desk.incidents.show', $incident)
            ->with('success', __('front_desk.flash.incident_updated'));
    }

    public function assign(Request $request, FrontDeskIncidentLog $incident)
    {
        $data = $request->validate(['assigned_to_user_id' => ['required', 'exists:users,id']]);
        $this->service->assign($incident, User::findOrFail($data['assigned_to_user_id']), $request->user());

        return back()->with('success', __('front_desk.flash.incident_assigned'));
    }

    public function escalate(Request $request, FrontDeskIncidentLog $incident)
    {
        $data = $request->validate([
            'escalated_to_user_id' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->service->escalate($incident, User::findOrFail($data['escalated_to_user_id']), $request->user(), $data['note'] ?? null);

        return back()->with('success', __('front_desk.flash.incident_escalated'));
    }

    public function resolve(Request $request, FrontDeskIncidentLog $incident)
    {
        $data = $request->validate(['resolution_note' => ['nullable', 'string', 'max:2000']]);
        $this->service->resolve($incident, $request->user(), $data['resolution_note'] ?? null);

        return back()->with('success', __('front_desk.flash.incident_resolved'));
    }

    public function cancel(Request $request, FrontDeskIncidentLog $incident)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->service->cancel($incident, $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('front_desk.flash.incident_cancelled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'incident_type' => ['required', 'string'],
            'severity' => ['required', 'string'],
            'reported_at' => ['nullable', 'date'],
            'reported_by_name' => ['nullable', 'string', 'max:255'],
            'reported_by_phone' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'related_visitor_log_id' => ['nullable', 'exists:front_desk_visitor_logs,id'],
            'related_call_log_id' => ['nullable', 'exists:front_desk_call_logs,id'],
            'related_courier_log_id' => ['nullable', 'exists:front_desk_courier_logs,id'],
            'description' => ['required', 'string', 'max:5000'],
            'action_taken' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'types' => FrontDeskIncidentType::cases(),
            'severities' => FrontDeskIncidentSeverity::cases(),
            'statuses' => FrontDeskIncidentStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'recentVisitors' => FrontDeskVisitorLog::query()->latest('id')->limit(30)->get(['id', 'visitor_name']),
            'recentCalls' => FrontDeskCallLog::query()->latest('id')->limit(30)->get(['id', 'caller_name', 'recipient_name']),
            'recentCouriers' => FrontDeskCourierLog::query()->latest('id')->limit(30)->get(['id', 'tracking_number', 'recipient_name']),
        ];
    }
}
