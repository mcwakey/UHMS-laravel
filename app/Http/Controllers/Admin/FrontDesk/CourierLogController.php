<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\CourierDirection;
use App\Enums\FrontDesk\CourierHandoverStatus;
use App\Enums\FrontDesk\CourierStatus;
use App\Enums\FrontDesk\CourierType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrontDesk\StoreCourierLogRequest;
use App\Http\Requests\FrontDesk\UpdateCourierLogRequest;
use App\Models\Department;
use App\Models\FrontDeskCourierLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\FrontDesk\CourierLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourierLogController extends Controller
{
    public function __construct(private CourierLogService $service) {}

    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.couriers.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'direction', 'courier_type', 'status', 'department_id', 'handover_status', 'quick']),
            'directions' => CourierDirection::cases(),
            'types' => CourierType::cases(),
            'statuses' => CourierStatus::cases(),
            'handoverStatuses' => CourierHandoverStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function filteredQuery(Request $request)
    {
        return FrontDeskCourierLog::query()
            ->with(['recipientDepartment', 'patient', 'receivedBy', 'sentBy', 'dispatchedBy'])
            ->search($request->string('search'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->direction($request->input('direction'))
            ->courierType($request->input('courier_type'))
            ->status($request->input('status'))
            ->handoverStatus($request->input('handover_status'))
            ->department($request->integer('department_id') ?: null)
            ->when($request->input('quick'), fn ($q, $quick) => $this->applyQuickFilter($q, $quick))
            ->latest('received_or_sent_at');
    }

    private function applyQuickFilter($query, string $quick)
    {
        return match ($quick) {
            'pending_dispatch' => $query->pendingDispatch(),
            'in_transit' => $query->inTransit(),
            'awaiting_handover' => $query->awaitingHandover(),
            'delivered_today' => $query->where('status', CourierStatus::DELIVERED->value)->whereDate('delivered_at', today()),
            'returned' => $query->returnedItems(),
            'overdue' => $query->overdueCourier(),
            default => $query,
        };
    }

    /** Courier workflow board — items still needing action. */
    public function workflow(Request $request)
    {
        $request->merge(['quick' => $request->input('quick', 'pending_dispatch')]);

        $logs = $this->filteredQuery($request)
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.couriers.workflow', [
            'logs' => $logs,
            'filters' => $request->only(['quick']),
            'quickFilters' => ['pending_dispatch', 'in_transit', 'awaiting_handover', 'delivered_today', 'returned', 'overdue'],
        ]);
    }

    public function create()
    {
        return view('admin.front-desk.couriers.create', $this->formData());
    }

    public function store(StoreCourierLogRequest $request)
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route(app(\App\Services\WorkspaceRouteResolver::class)->routeName('admin.front-desk.couriers.index'))
            ->with('success', __('front_desk.flash.courier_created'));
    }

    public function show(FrontDeskCourierLog $courier)
    {
        $courier->load([
            'recipientDepartment', 'patient', 'visit', 'receivedBy', 'sentBy',
            'dispatchDepartment', 'dispatchedBy', 'receivedInternallyBy',
            'handoffs.createdBy', 'handoffs.toUser', 'handoffs.toDepartment',
        ]);

        return view('admin.front-desk.couriers.show', array_merge($this->formData(), ['log' => $courier]));
    }

    public function edit(FrontDeskCourierLog $courier)
    {
        return view('admin.front-desk.couriers.edit', array_merge($this->formData(), ['log' => $courier]));
    }

    public function update(UpdateCourierLogRequest $request, FrontDeskCourierLog $courier)
    {
        $this->service->update($courier, $request->validated(), $request->user());

        return redirect()
            ->route(app(\App\Services\WorkspaceRouteResolver::class)->routeName('admin.front-desk.couriers.show'), $courier)
            ->with('success', __('front_desk.flash.courier_updated'));
    }

    public function dispatchItem(Request $request, FrontDeskCourierLog $courier)
    {
        $data = $request->validate([
            'dispatch_department_id' => ['nullable', 'exists:departments,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->dispatch($courier, $data, $request->user());

        return back()->with('success', __('front_desk.flash.courier_dispatched'));
    }

    public function handover(Request $request, FrontDeskCourierLog $courier)
    {
        $data = $request->validate([
            'to_user_id' => ['nullable', 'exists:users,id'],
            'to_department_id' => ['nullable', 'exists:departments,id'],
            'received_internally_by' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->handOver($courier, $data, $request->user());

        return back()->with('success', __('front_desk.flash.courier_handed_over'));
    }

    public function markDelivered(Request $request, FrontDeskCourierLog $courier)
    {
        $data = $request->validate([
            'proof_reference' => ['nullable', 'string', 'max:255'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $this->service->markDelivered(
            $courier,
            $request->user(),
            ! empty($data['delivered_at']) ? Carbon::parse($data['delivered_at']) : null,
            $data['delivery_note'] ?? null,
            $data['proof_reference'] ?? null,
        );

        return back()->with('success', __('front_desk.flash.courier_delivered'));
    }

    public function markReturned(Request $request, FrontDeskCourierLog $courier)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $this->service->markReturned($courier, $request->user(), $data['reason'] ?? null);

        return back()->with('success', __('front_desk.flash.courier_returned'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'directions' => CourierDirection::cases(),
            'types' => CourierType::cases(),
            'statuses' => CourierStatus::cases(),
            'handoverStatuses' => CourierHandoverStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'patients' => Patient::orderByDesc('id')->limit(50)->get(['id', 'patient_number', 'first_name', 'last_name']),
            'users' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ];
    }
}
