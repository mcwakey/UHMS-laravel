<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\ShiftHandoverStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FrontDeskShiftHandover;
use App\Models\User;
use App\Services\FrontDesk\ShiftHandoverService;
use Illuminate\Http\Request;

class ShiftHandoverController extends Controller
{
    public function __construct(private ShiftHandoverService $service) {}

    public function index(Request $request)
    {
        $logs = FrontDeskShiftHandover::query()
            ->with(['outgoingUser', 'incomingUser', 'department'])
            ->search($request->string('search'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->status($request->input('status'))
            ->department($request->integer('department_id') ?: null)
            ->latest('shift_date')->latest('id')
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.handovers.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'status', 'department_id']),
            'statuses' => ShiftHandoverStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('admin.front-desk.handovers.create', array_merge($this->formData(), [
            'snapshot' => $this->service->buildOpenItemsSnapshot(),
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shift_date' => ['nullable', 'date'],
            'shift_name' => ['nullable', 'string', 'max:100'],
            'incoming_user_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'summary_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $handover = $this->service->createDraft($data, $request->user());

        return redirect()->route('admin.front-desk.handovers.show', $handover)
            ->with('success', __('front_desk.flash.handover_created'));
    }

    public function show(FrontDeskShiftHandover $handover)
    {
        $handover->load(['outgoingUser', 'incomingUser', 'department', 'submittedBy', 'acceptedBy', 'cancelledBy']);

        return view('admin.front-desk.handovers.show', array_merge($this->formData(), ['log' => $handover]));
    }

    public function edit(FrontDeskShiftHandover $handover)
    {
        abort_unless($handover->isDraft(), 403);

        return view('admin.front-desk.handovers.edit', array_merge($this->formData(), ['log' => $handover]));
    }

    public function update(Request $request, FrontDeskShiftHandover $handover)
    {
        $data = $request->validate([
            'shift_date' => ['nullable', 'date'],
            'shift_name' => ['nullable', 'string', 'max:100'],
            'incoming_user_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'summary_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->service->update($handover, $data, $request->user());

        return redirect()->route('admin.front-desk.handovers.show', $handover)
            ->with('success', __('front_desk.flash.handover_updated'));
    }

    public function submit(Request $request, FrontDeskShiftHandover $handover)
    {
        $data = $request->validate(['summary_notes' => ['nullable', 'string', 'max:5000']]);
        $this->service->submit($handover, $request->user(), $data['summary_notes'] ?? null);

        return back()->with('success', __('front_desk.flash.handover_submitted'));
    }

    public function accept(Request $request, FrontDeskShiftHandover $handover)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $this->service->accept($handover, $request->user(), $data['note'] ?? null);

        return back()->with('success', __('front_desk.flash.handover_accepted'));
    }

    public function cancel(Request $request, FrontDeskShiftHandover $handover)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $this->service->cancel($handover, $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('front_desk.flash.handover_cancelled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'statuses' => ShiftHandoverStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
