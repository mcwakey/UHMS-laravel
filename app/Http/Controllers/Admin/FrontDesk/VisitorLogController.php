<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Enums\FrontDesk\VisitorContext;
use App\Enums\FrontDesk\VisitorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrontDesk\StoreVisitorLogRequest;
use App\Http\Requests\FrontDesk\UpdateVisitorLogRequest;
use App\Models\Admission;
use App\Models\Department;
use App\Models\FrontDeskVisitorLog;
use App\Models\Patient;
use App\Models\Ward;
use App\Services\FrontDesk\PatientVisitorRuleService;
use App\Services\FrontDesk\VisitorLogService;
use Illuminate\Http\Request;

class VisitorLogController extends Controller
{
    public function __construct(
        private VisitorLogService $service,
        private PatientVisitorRuleService $rules,
    ) {}

    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.visitors.index', [
            'logs' => $logs,
            'filters' => $request->only([
                'search', 'date_from', 'date_to', 'status', 'visitor_context',
                'department_id', 'quick', 'patient_id', 'admission_id',
            ]),
            'statuses' => VisitorStatus::cases(),
            'contexts' => VisitorContext::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'quickFilters' => ['inside', 'overdue', 'patient', 'facility', 'checked_out_today', 'denied_cancelled'],
        ]);
    }

    /** Filtered visitor query shared by the index and the history screens. */
    private function filteredQuery(Request $request)
    {
        return FrontDeskVisitorLog::query()
            ->with(['patient', 'department', 'ward', 'checkedInBy', 'checkedOutBy'])
            ->search($request->string('search'))
            ->dateRange($request->input('date_from'), $request->input('date_to'))
            ->status($request->input('status'))
            ->visitorContext($request->input('visitor_context'))
            ->department($request->integer('department_id') ?: null)
            ->when($request->integer('patient_id') ?: null, fn ($q, $id) => $q->forPatient($id))
            ->when($request->integer('admission_id') ?: null, fn ($q, $id) => $q->forAdmission($id))
            ->when($request->input('quick'), fn ($q, $quick) => $this->applyQuickFilter($q, $quick))
            ->latest('time_in');
    }

    private function applyQuickFilter($query, string $quick)
    {
        return match ($quick) {
            'inside' => $query->currentlyInside(),
            'overdue' => $query->overdue(),
            'patient' => $query->patientLinked(),
            'facility' => $query->facilityVisitor(),
            'checked_out_today' => $query->checkedOutToday(),
            'denied_cancelled' => $query->whereIn('status', [VisitorStatus::DENIED->value, VisitorStatus::CANCELLED->value]),
            default => $query,
        };
    }

    public function create(Request $request)
    {
        $patient = $request->integer('patient_id')
            ? Patient::with('activeAdmission.bed.ward')->find($request->integer('patient_id'))
            : null;

        return view('admin.front-desk.visitors.create', array_merge($this->formData(), [
            'preselectedPatient' => $patient,
            'warnings' => $patient ? $this->rules->warningsForPatient($patient) : [],
        ]));
    }

    public function store(StoreVisitorLogRequest $request)
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.front-desk.visitors.index')
            ->with('success', __('front_desk.flash.visitor_created'));
    }

    public function show(FrontDeskVisitorLog $visitor)
    {
        $visitor->load(['patient.activeAdmission.bed.ward', 'visit', 'admission', 'ward', 'bed', 'department', 'checkedInBy', 'checkedOutBy', 'approvedBy']);

        return view('admin.front-desk.visitors.show', [
            'log' => $visitor,
            'warnings' => $visitor->patient
                ? $this->rules->warningsForPatient($visitor->patient, $visitor->admission)
                : [],
        ]);
    }

    public function edit(FrontDeskVisitorLog $visitor)
    {
        return view('admin.front-desk.visitors.edit', array_merge($this->formData(), [
            'log' => $visitor,
            'warnings' => $visitor->patient
                ? $this->rules->warningsForPatient($visitor->patient, $visitor->admission)
                : [],
        ]));
    }

    public function update(UpdateVisitorLogRequest $request, FrontDeskVisitorLog $visitor)
    {
        $this->service->update($visitor, $request->validated(), $request->user());

        return redirect()
            ->route('admin.front-desk.visitors.show', $visitor)
            ->with('success', __('front_desk.flash.visitor_updated'));
    }

    public function checkOut(Request $request, FrontDeskVisitorLog $visitor)
    {
        $validated = $request->validate([
            'checkout_note' => ['nullable', 'string', 'max:1000'],
            'time_out' => ['nullable', 'date', 'after_or_equal:' . optional($visitor->time_in)->toDateTimeString()],
        ]);

        $this->service->checkOut(
            $visitor,
            $request->user(),
            ! empty($validated['time_out']) ? \Illuminate\Support\Carbon::parse($validated['time_out']) : null,
            $validated['checkout_note'] ?? null,
        );

        return back()->with('success', __('front_desk.flash.visitor_checked_out'));
    }

    /** Printable, print-friendly visitor pass (safe identifiers only). */
    public function pass(Request $request, FrontDeskVisitorLog $visitor)
    {
        $visitor->load(['patient', 'ward', 'bed', 'department', 'checkedInBy']);

        // Audit the print only when the viewer actually holds the print permission.
        if ($request->user()?->can('front_desk.visitors.print_pass')) {
            $this->service->logPassPrinted($visitor, $request->user());
        }

        return view('admin.front-desk.visitors.pass', ['log' => $visitor]);
    }

    /** Visitor history for a patient (filtered index). */
    public function patientHistory(Request $request, Patient $patient)
    {
        $request->merge(['patient_id' => $patient->id]);

        $logs = $this->filteredQuery($request)
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.visitors.history', [
            'logs' => $logs,
            'heading' => __('front_desk.visitors.patient_history_for', ['patient' => $patient->patient_number]),
            'backRoute' => route('admin.front-desk.visitors.index'),
        ]);
    }

    /** Visitor history for a specific admission (filtered index). */
    public function admissionHistory(Request $request, Admission $admission)
    {
        $request->merge(['admission_id' => $admission->id]);

        $logs = $this->filteredQuery($request)
            ->paginate((int) config('front_desk.per_page', 20))
            ->withQueryString();

        return view('admin.front-desk.visitors.history', [
            'logs' => $logs,
            'heading' => __('front_desk.visitors.admission_history_for', ['admission' => $admission->admission_number]),
            'backRoute' => route('admin.front-desk.visitors.index'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'contexts' => VisitorContext::cases(),
            'statuses' => VisitorStatus::cases(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'wards' => Ward::orderBy('name')->get(['id', 'name']),
            'patients' => Patient::orderByDesc('id')->limit(50)->get(['id', 'patient_number', 'first_name', 'last_name']),
        ];
    }
}
