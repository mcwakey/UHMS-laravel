<?php

namespace App\Http\Controllers\Theatre;

use App\Enums\ProcedureStatus;
use App\Http\Controllers\Controller;
use App\Models\ProcedureRequest;
use App\Models\TheatreRoom;
use App\Models\User;
use App\Models\Visit;
use App\Services\ConsumableUsageService;
use App\Services\InpatientWorkspaceScope;
use App\Services\ProcedureCatalogueService;
use App\Services\ProcedureClinicalService;
use App\Services\ProcedureReportService;
use App\Services\ProcedureRequestService;
use App\Services\ProcedureScheduleService;
use App\Services\ProcedureTemplateService;
use App\Services\ProcedureWorkflowService;
use App\Services\ServiceConsumableService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TheatreController extends Controller
{
    public function __construct(
        protected ProcedureRequestService $requestService,
        protected ProcedureWorkflowService $workflow,
        protected ProcedureScheduleService $scheduleService,
        protected ProcedureClinicalService $clinical,
        protected ProcedureReportService $reports,
        protected ProcedureTemplateService $templates,
        protected ConsumableUsageService $consumableUsage,
        protected ServiceConsumableService $serviceConsumables,
        protected InpatientWorkspaceScope $inpatientScope,
    ) {}

    /**
     * Persist dynamic template values for a stage if any are submitted.
     */
    protected function saveStageTemplateValues(Request $request, ProcedureRequest $procedure, string $templateType): void
    {
        $values = $request->input('template_values', []);
        if (! is_array($values) || empty($values)) {
            return;
        }
        $procedure->loadMissing('service');
        if (! $procedure->service) {
            return;
        }
        $this->templates->saveValues($procedure, $templateType, $values, Auth::id());
    }

    /**
     * Record consumable usage for a stage if any consumables were submitted.
     * Items shape: [['product_id'=>..,'quantity'=>..,'notes'=>..], ...]
     */
    protected function saveStageConsumables(Request $request, ProcedureRequest $procedure): void
    {
        $items = $request->input('consumables', []);
        if (! is_array($items) || empty($items)) {
            return;
        }
        $items = array_values(array_filter($items, function ($row) {
            return is_array($row)
                && ! empty($row['product_id'])
                && isset($row['quantity'])
                && (float) $row['quantity'] > 0;
        }));
        if (empty($items)) {
            return;
        }
        $procedure->loadMissing(['visit', 'service']);
        if (! $procedure->visit) {
            return;
        }
        $this->consumableUsage->recordUsageForSource(
            $procedure->visit,
            $procedure->service,
            'procedure_request',
            $procedure->id,
            $items,
            Auth::id(),
        );
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->ajax() && ! $request->headers->has('X-Inertia');
    }

    /**
     * Theatre dashboard with filterable queues.
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'pending');

        $statusFilter = match ($tab) {
            'pending' => [ProcedureStatus::REQUESTED],
            'accepted' => [ProcedureStatus::ACCEPTED],
            'billed' => [ProcedureStatus::BILLED],
            'scheduled' => [ProcedureStatus::SCHEDULED, ProcedureStatus::RESCHEDULED],
            'in_theatre' => [ProcedureStatus::PRE_OP, ProcedureStatus::ANAESTHESIA, ProcedureStatus::IN_SURGERY, ProcedureStatus::SURGERY_DONE],
            'recovery' => [ProcedureStatus::POST_OP],
            'completed' => [ProcedureStatus::COMPLETED],
            'closed' => [ProcedureStatus::CANCELLED, ProcedureStatus::REJECTED],
            default => ProcedureStatus::openStatuses(),
        };

        $query = ProcedureRequest::with([
            'patient', 'visit', 'service', 'department', 'requestingDoctor',
            'schedule.theatreRoom', 'schedule.surgeon', 'schedule.anaesthetist',
            'billingItem.invoice',
        ])->whereIn('status', array_map(fn ($s) => $s->value, $statusFilter));

        if ($this->shouldScopeToLoggedInDoctor($request)) {
            $query->where('requested_by', $request->user()->id);
        }

        if ($request->routeIs('inpatient.*')) {
            $this->inpatientScope->procedureRequests($query);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('request_number', 'like', "%{$s}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('patient_number', 'like', "%{$s}%"))
                    ->orWhereHas('visit', fn ($v) => $v->where('visit_number', 'like', "%{$s}%"));
            });
        }

        if ($prio = $request->input('priority')) {
            $query->where('priority', $prio);
        }

        if ($roomId = $request->integer('room_id')) {
            $query->whereHas('schedule', fn ($schedule) => $schedule->where('theatre_room_id', $roomId));
        }

        if ($date = $request->input('date')) {
            $day = Carbon::parse($date);
            $query->whereHas('schedule', fn ($schedule) => $schedule
                ->whereBetween('scheduled_start', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]));
        }

        $requests = $query->latest('id')->paginate(20)->withQueryString();
        $rooms = TheatreRoom::query()->orderBy('name')->get(['id', 'name', 'code']);

        $statsScope = function ($statsQuery) use ($request) {
            if ($this->shouldScopeToLoggedInDoctor($request)) {
                $statsQuery->where('requested_by', $request->user()->id);
            }
            if ($request->routeIs('inpatient.*')) {
                $this->inpatientScope->procedureRequests($statsQuery);
            }

            return $statsQuery;
        };

        $stats = [
            'pending' => $statsScope(ProcedureRequest::where('status', ProcedureStatus::REQUESTED->value))->count(),
            'accepted' => $statsScope(ProcedureRequest::where('status', ProcedureStatus::ACCEPTED->value))->count(),
            'billed' => $statsScope(ProcedureRequest::where('status', ProcedureStatus::BILLED->value))->count(),
            'scheduled' => $statsScope(ProcedureRequest::whereIn('status', [ProcedureStatus::SCHEDULED->value, ProcedureStatus::RESCHEDULED->value]))->count(),
            'in_theatre' => $statsScope(ProcedureRequest::whereIn('status', [
                ProcedureStatus::PRE_OP->value, ProcedureStatus::ANAESTHESIA->value,
                ProcedureStatus::IN_SURGERY->value, ProcedureStatus::SURGERY_DONE->value,
            ]))->count(),
            'recovery' => $statsScope(ProcedureRequest::where('status', ProcedureStatus::POST_OP->value))->count(),
            'completed_today' => $statsScope(ProcedureRequest::where('status', ProcedureStatus::COMPLETED->value)
                ->whereDate('completed_at', today()))->count(),
        ];

        return view('theatre.index', compact('requests', 'stats', 'tab', 'rooms'));
    }

    public function show(ProcedureRequest $procedure)
    {
        $this->authorizeDoctorWorkspaceProcedure($procedure);
        abort_if(request()->routeIs('inpatient.*') && ! $this->inpatientScope->contains($procedure), 404);

        $procedure->load([
            'patient', 'visit', 'service', 'department', 'requestingDoctor',
            'schedule.theatreRoom', 'schedule.surgeon', 'schedule.anaesthetist', 'schedule.assistantSurgeon',
            'vitals.recordedBy', 'checklist.completedBy',
            'anaesthesiaNote.anaesthetist',
            'operativeNote.surgeon', 'operativeNote.assistantSurgeon',
            'postOpNote.recordedBy',
            'billingItem.invoice',
            'statusLogs.changedBy',
        ]);

        $timeline = $this->reports->getTimeline($procedure);
        $theatreRooms = TheatreRoom::schedulable()->orderBy('name')->get();
        $clinicians = User::query()->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $billingPreview = $this->workflow->billingPreview($procedure);

        // Dynamic procedure template (Phase A): build per-stage section/field views with saved values.
        $stageTemplates = [];
        if ($procedure->service) {
            foreach (ProcedureCatalogueService::TEMPLATE_TYPES as $tt) {
                $stageTemplates[$tt] = $this->templates->buildStageView($procedure->service, $tt, $procedure);
            }
        }

        // Default consumables for the service (used to prepopulate the consumables picker).
        $defaultConsumables = $procedure->service
            ? $this->consumableUsage->defaultsForService($procedure->service->id)
            : collect();

        return view('theatre.show', compact(
            'procedure', 'timeline', 'theatreRooms', 'clinicians',
            'stageTemplates', 'defaultConsumables', 'billingPreview',
        ));
    }

    public function fullReport(ProcedureRequest $procedure)
    {
        if ($procedure->status !== ProcedureStatus::COMPLETED) {
            return back()->with('error', __('messages.theatre.report_unavailable'));
        }

        $procedure->load([
            'patient', 'visit.department', 'service', 'department', 'requestingDoctor',
            'schedule.theatreRoom', 'schedule.surgeon', 'schedule.anaesthetist', 'schedule.assistantSurgeon',
            'vitals.recordedBy', 'checklist.completedBy',
            'anaesthesiaNote.anaesthetist',
            'operativeNote.surgeon', 'operativeNote.assistantSurgeon',
            'postOpNote.recordedBy',
            'billingItem.invoice',
        ]);

        $timeline = $this->reports->getTimeline($procedure);

        return view('theatre.report', compact('procedure', 'timeline'));
    }

    /* ── Doctor: request a procedure (from consultation page) ── */
    public function requestStore(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'procedure_id' => ['nullable', 'exists:procedures,id'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'indication' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
        ]);
        $data['visit_id'] = $visit->id;

        try {
            $procedure = $this->requestService->requestProcedure($data, Auth::user());
        } catch (\Throwable $e) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => __('messages.theatre.procedure_submitted_number', ['number' => $procedure->request_number]),
                'procedure' => $procedure->only(['id', 'request_number', 'status', 'priority']),
            ]);
        }

        return back()->withFragment('procedures-section')->with('success', __('messages.theatre.procedure_submitted'));
    }

    /* ── Workflow actions ── */

    public function accept(Request $request, ProcedureRequest $procedure)
    {
        try {
            $this->workflow->acceptProcedure($procedure, Auth::user(), $request->input('notes'));
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_accepted'));
    }

    public function reject(Request $request, ProcedureRequest $procedure)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        try {
            $this->workflow->rejectProcedure($procedure, Auth::user(), $request->input('reason'));
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_rejected'));
    }

    public function generateBilling(Request $request, ProcedureRequest $procedure)
    {
        try {
            $this->workflow->generateBilling($procedure, Auth::user());
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_billed'));
    }

    public function schedule(Request $request, ProcedureRequest $procedure)
    {
        $data = $request->validate([
            'theatre_room_id' => ['required', 'exists:theatre_rooms,id'],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after:scheduled_start'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'surgeon_id' => ['nullable', 'exists:users,id'],
            'anaesthetist_id' => ['nullable', 'exists:users,id'],
            'assistant_surgeon_id' => ['nullable', 'exists:users,id'],
            'required_equipment' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'override_room_conflict' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'required_if:override_room_conflict,1', 'string', 'max:1000'],
        ]);

        try {
            $this->scheduleService->scheduleProcedure($procedure, $data, Auth::user());
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_scheduled'));
    }

    public function reschedule(Request $request, ProcedureRequest $procedure)
    {
        $data = $request->validate([
            'theatre_room_id' => ['required', 'exists:theatre_rooms,id'],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after:scheduled_start'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'surgeon_id' => ['nullable', 'exists:users,id'],
            'anaesthetist_id' => ['nullable', 'exists:users,id'],
            'assistant_surgeon_id' => ['nullable', 'exists:users,id'],
            'required_equipment' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:500'],
            'override_room_conflict' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'required_if:override_room_conflict,1', 'string', 'max:1000'],
        ]);

        try {
            $this->scheduleService->reschedule($procedure, $data, Auth::user(), $data['reason']);
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_rescheduled'));
    }

    public function preop(Request $request, ProcedureRequest $procedure)
    {
        $data = $request->validate([
            'vitals.temperature' => ['nullable', 'numeric'],
            'vitals.blood_pressure' => ['nullable', 'string', 'max:20'],
            'vitals.pulse' => ['nullable', 'integer'],
            'vitals.respiratory_rate' => ['nullable', 'integer'],
            'vitals.oxygen_saturation' => ['nullable', 'integer'],
            'vitals.weight' => ['nullable', 'numeric'],
            'vitals.pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'vitals.notes' => ['nullable', 'string', 'max:1000'],
            'consent_signed' => ['nullable', 'boolean'],
            'fasting_confirmed' => ['nullable', 'boolean'],
            'allergies_checked' => ['nullable', 'boolean'],
            'blood_available' => ['nullable', 'boolean'],
            'site_marked' => ['nullable', 'boolean'],
            'equipment_ready' => ['nullable', 'boolean'],
            'anaesthesia_review_done' => ['nullable', 'boolean'],
            'pre_op_diagnosis' => ['nullable', 'string', 'max:255'],
            'checklist_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->clinical->recordPreOp($procedure, $data, Auth::user());
            $this->saveStageTemplateValues($request, $procedure, ProcedureCatalogueService::TPL_PRE_OP);
            $this->saveStageConsumables($request, $procedure);
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.preop_saved'));
    }

    public function anaesthesia(Request $request, ProcedureRequest $procedure)
    {
        $data = $request->validate([
            'anaesthetist_id' => ['nullable', 'exists:users,id'],
            'anaesthesia_type' => ['required', 'in:local,regional,spinal,general,sedation,other'],
            'pre_assessment' => ['nullable', 'string', 'max:2000'],
            'drugs_used' => ['nullable', 'string', 'max:2000'],
            'dosage_notes' => ['nullable', 'string', 'max:2000'],
            'airway_management' => ['nullable', 'string', 'max:2000'],
            'monitoring_notes' => ['nullable', 'string', 'max:2000'],
            'complications' => ['nullable', 'string', 'max:2000'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->clinical->recordAnaesthesiaNote($procedure, $data, Auth::user());
            $this->saveStageTemplateValues($request, $procedure, ProcedureCatalogueService::TPL_ANAESTHESIA);
            $this->saveStageConsumables($request, $procedure);
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.anaesthesia_saved'));
    }

    public function startSurgery(Request $request, ProcedureRequest $procedure)
    {
        try {
            $this->clinical->startSurgery($procedure, Auth::user());
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.surgery_started'));
    }

    public function operativeNote(Request $request, ProcedureRequest $procedure)
    {
        $data = $request->validate([
            'surgeon_id' => ['nullable', 'exists:users,id'],
            'assistant_surgeon_id' => ['nullable', 'exists:users,id'],
            'procedure_performed' => ['required', 'string', 'max:500'],
            'pre_op_diagnosis' => ['nullable', 'string', 'max:1000'],
            'post_op_diagnosis' => ['nullable', 'string', 'max:1000'],
            'findings' => ['nullable', 'string', 'max:3000'],
            'incision' => ['nullable', 'string', 'max:500'],
            'technique' => ['nullable', 'string', 'max:3000'],
            'blood_loss' => ['nullable', 'string', 'max:100'],
            'complications' => ['nullable', 'string', 'max:2000'],
            'specimens' => ['nullable', 'string', 'max:1000'],
            'implants' => ['nullable', 'string', 'max:1000'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'outcome' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'completed' => ['nullable', 'boolean'],
        ]);

        try {
            $this->clinical->recordOperativeNote($procedure, $data, Auth::user());
            $this->saveStageTemplateValues($request, $procedure, ProcedureCatalogueService::TPL_OPERATIVE_NOTE);
            $this->saveStageConsumables($request, $procedure);
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.operative_note_saved'));
    }

    public function completeSurgery(Request $request, ProcedureRequest $procedure)
    {
        try {
            $this->clinical->completeSurgery($procedure, Auth::user());
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.surgery_done'));
    }

    public function postop(Request $request, ProcedureRequest $procedure)
    {
        $data = $request->validate([
            'recovery_status' => ['nullable', 'string', 'max:255'],
            'pain_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'consciousness_level' => ['nullable', 'string', 'max:100'],
            'post_op_instructions' => ['nullable', 'string', 'max:3000'],
            'medications' => ['nullable', 'string', 'max:2000'],
            'complications' => ['nullable', 'string', 'max:2000'],
            'transfer_destination' => ['nullable', 'in:ward,icu,outpatient,emergency_obs,recovery_room'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vitals.temperature' => ['nullable', 'numeric'],
            'vitals.blood_pressure' => ['nullable', 'string', 'max:20'],
            'vitals.pulse' => ['nullable', 'integer'],
            'vitals.respiratory_rate' => ['nullable', 'integer'],
            'vitals.oxygen_saturation' => ['nullable', 'integer'],
            'vitals.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->clinical->recordPostOp($procedure, $data, Auth::user());
            $this->saveStageTemplateValues($request, $procedure, ProcedureCatalogueService::TPL_POST_OP);
            $this->saveStageConsumables($request, $procedure);
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.postop_saved'));
    }

    public function complete(Request $request, ProcedureRequest $procedure)
    {
        try {
            $this->workflow->completeProcedure($procedure, Auth::user());
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_completed'));
    }

    public function cancel(Request $request, ProcedureRequest $procedure)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        try {
            $this->workflow->cancelProcedure($procedure, Auth::user(), $request->input('reason'));
        } catch (\Throwable $e) {
            return $this->errorBack($request, $e);
        }

        return $this->successBack($request, __('messages.theatre.procedure_cancelled'));
    }

    /* ── Helpers ─────────────────────────────────────────── */

    protected function errorBack(Request $request, \Throwable $e)
    {
        if ($this->shouldReturnJson($request)) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return back()->withInput()->with('error', $e->getMessage());
    }

    protected function successBack(Request $request, string $msg)
    {
        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => $msg]);
        }

        return back()->with('success', $msg);
    }

    private function shouldScopeToLoggedInDoctor(Request $request): bool
    {
        return $request->routeIs('doctor.*') && ! ($request->user()?->hasRole('Super Admin') ?? false);
    }

    private function authorizeDoctorWorkspaceProcedure(ProcedureRequest $procedure): void
    {
        $request = request();

        abort_if(
            $this->shouldScopeToLoggedInDoctor($request)
                && (int) $procedure->requested_by !== (int) $request->user()?->id,
            404
        );
    }
}
