<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingType;
use App\Enums\DepartmentType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingService;
use App\Services\InsuranceService;
use App\Services\QueueService;
use App\Services\VisitService;
use App\Services\VisitWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VisitController extends Controller
{
    public function __construct(
        protected VisitService $visitService,
        protected InsuranceService $insuranceService,
        protected QueueService $queueService,
        protected BillingService $billingService,
        protected VisitWorkflowService $visitWorkflowService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->all();

        // Default to today's visits if no date filter is set
        if (empty($filters['date_from']) && empty($filters['search'])) {
            $filters['date_from'] = today()->toDateString();
            $filters['date_to'] = today()->toDateString();
        }

        $visits = $this->visitService->list($filters);
        $stats = $this->visitService->todayStats();
        $doctors = User::whereHas('roles', fn ($query) => $query->whereIn('name', User::CONSULTATION_ROLES))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        // True-Inertia migration (Phase B / B4): serialize for Vue page.
        $user = $request->user();

        $visitTypeColor = static fn (VisitType $t): string => match ($t) {
            VisitType::EMERGENCY => 'danger',
            VisitType::INPATIENT => 'info',
            default => 'light text-dark',
        };

        $visitsPayload = $visits->through(function (Visit $visit) use ($visitTypeColor) {
            return [
                'id' => $visit->id,
                'visit_number' => $visit->visit_number,
                'visit_date_display' => optional($visit->visit_date)->format('d M Y'),
                'duration' => $visit->duration,
                'age_display' => $visit->patient_age ?? optional($visit->patient)->age,
                'route_doctor_name' => optional($visit->currentConsultationDoctor())->full_name,
                'patient' => $visit->patient ? [
                    'id' => $visit->patient->id,
                    'full_name' => $visit->patient->full_name,
                    'patient_number' => $visit->patient->patient_number,
                ] : null,
                'visit_type' => $visit->visit_type ? [
                    'value' => $visit->visit_type->value,
                    'label' => $visit->visit_type->label(),
                    'color' => $visitTypeColor($visit->visit_type),
                ] : null,
                'priority' => $visit->priority ? [
                    'value' => $visit->priority->value,
                    'label' => $visit->priority->label(),
                    'color' => $visit->priority->color(),
                ] : null,
                'triage_score' => $visit->triage_score ? [
                    'value' => $visit->triage_score->value,
                    'label' => $visit->triage_score->label(),
                    'color' => $visit->triage_score->color(),
                ] : null,
                'status' => $visit->status ? [
                    'value' => $visit->status->value,
                    'label' => $visit->status->label(),
                    'color' => $visit->status->color(),
                ] : null,
                'allowed_transitions' => collect($visit->status?->allowedTransitions() ?? [])
                    ->map(fn (VisitStatus $s) => [
                        'value' => $s->value,
                        'label' => $s->label(),
                    ])->values()->all(),
                'urls' => [
                    'show' => route('admin.visits.show', $visit),
                    'edit' => route('admin.visits.edit', $visit),
                    'patient' => $visit->patient ? route('admin.patients.show', $visit->patient) : null,
                ],
            ];
        });

        return Inertia::render('Visits/Index', [
            'visits' => $visitsPayload,
            'stats' => $stats,
            'doctors' => $doctors->map(fn ($d) => [
                'id' => $d->id,
                'full_name' => $d->full_name,
            ])->values(),
            'filters' => $filters,
            'statusOptions' => collect(VisitStatus::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'visitTypeOptions' => collect(VisitType::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
            'routes' => [
                'index' => route('admin.visits.index'),
                'create' => route('admin.visits.create'),
                'queueBoard' => route('admin.queue.board'),
                // {visit} placeholder swapped client-side per row.
                'transition' => route('admin.visits.transition', ['visit' => '__ID__']),
            ],
            'can' => [
                'create' => $user?->can('visits.create') ?? false,
                'edit' => $user?->can('visits.edit') ?? false,
                'queueView' => $user?->can('queue.view') ?? false,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $selectedPatient = null;

        if ($request->has('patient_id')) {
            $selectedPatient = Patient::with('activeAdmission.bed.ward')->find($request->patient_id);
        }

        // Insurance providers (excluding the synthetic Cash & Carry default)
        // and their tiers, for the Add/Edit Insurance modal on this page.
        $insuranceProviders = InsuranceProvider::where('is_active', true)
            ->where(function ($q) {
                $q->where('is_default', false)->orWhereNull('is_default');
            })
            ->with(['tiers' => fn ($t) => $t->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get();

        $canOverrideActiveAdmission = $request->user()?->can('visits.create_while_admitted') ?? false;

        return view('visits.create', compact('departments', 'selectedPatient', 'insuranceProviders', 'canOverrideActiveAdmission'));
    }

    public function store(StoreVisitRequest $request)
    {
        // Block new visits for deceased patients (unless user has override permission)
        $patientId = $request->input('patient_id');
        if ($patientId) {
            $patient = Patient::find($patientId);
            if ($patient && $patient->is_deceased && ! $request->user()->can('patients.deceased.override')) {
                $error = 'This patient is marked as deceased and cannot start a new visit.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $error], 422);
                }

                return back()->withErrors(['patient_id' => $error])->withInput();
            }
        }

        try {
            $visit = DB::transaction(function () use ($request) {
                $v = $this->visitService->create($request->validated());

                // Attach selected services inside the same transaction.
                // attachServices() is the SINGLE source of truth for visit-creation
                // billing: it calls BillingService::addItemToVisitInvoice() once per
                // service with source_type='service_catalog'. Do NOT re-bill from
                // visit_services here — doing so causes duplicate invoice items
                // (different source_type bypasses the duplicate guard).
                $services = $request->validated()['services'] ?? [];
                if (! empty($services)) {
                    $this->visitService->attachServices($v, $services);
                }

                // Triage is only meaningful for patients who will see a doctor
                // for a consultation. Visits that only contain lab, pharmacy,
                // procedure or billing-only items skip triage entirely and stay
                // at REGISTERED status (the relevant department picks them up
                // from its own queue).
                $v = $v->fresh();
                if ($v->status !== VisitStatus::SCHEDULED
                    && $v->pendingConsultationRoutes()->exists()) {
                    $v = $this->visitWorkflowService->queueForTriage($v);
                }

                return $v;
            });
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('VisitController::store failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->except('_token'),
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to create visit. Please try again.',
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'Failed to create visit. Please try again.');
        }

        $message = match (true) {
            $visit->status === VisitStatus::SCHEDULED => "Visit {$visit->visit_number} scheduled successfully.",
            $visit->status === VisitStatus::WAITING => "Visit {$visit->visit_number} created and patient added to triage queue.",
            default => "Visit {$visit->visit_number} created. No consultation service selected — triage skipped.",
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'visit_id' => $visit->id,
                'visit_number' => $visit->visit_number,
                'status' => $visit->status->value,
                'status_label' => $visit->status->label(),
                'redirect_url' => route('admin.visits.show', $visit),
            ], 201);
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', $message);
    }

    public function edit(Visit $visit)
    {
        $visit->load([
            'patient',
            'visitInsurance.insuranceProvider',
            'visitServices.serviceCatalog',
            'activeConsultationRoute.doctor',
            'pendingConsultationRoutes.doctor',
        ]);

        $departments = Department::active()->orderBy('name')->get();
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        return view('visits.edit', compact('visit', 'departments', 'doctors'));
    }

    public function update(UpdateVisitRequest $request, Visit $visit)
    {
        try {
            DB::transaction(function () use ($request, $visit) {
                $data = $request->validated();
                $services = $data['services'] ?? null;
                unset($data['services']);

                $this->visitService->update($visit, $data);

                if ($services !== null) {
                    // Delete existing service items before re-attaching to avoid duplicates
                    $visit->visitServices()->delete();
                    if (! empty($services)) {
                        $this->visitService->attachServices($visit, $services);
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update visit: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', "Visit {$visit->visit_number} updated.");
    }

    public function show(Visit $visit)
    {
        $visit->load([
            'patient',
            'createdBy',
            'consultationRoutes.department',
            'consultationRoutes.service',
            'consultationRoutes.services',
            'consultationRoutes.routeServices.service',
            'consultationRoutes.doctor',
            'consultationRoutes.medicalRecord',
            'consultationRoutes.logs.performedBy',
            'activeConsultationRoute.doctor',
            'activeConsultationRoute.routeServices.service',
            'pendingConsultationRoutes.doctor',
            'pendingConsultationRoutes.routeServices.service',
            'statusLogs.changedBy',
            'queueEntries.department',
            'visitInsurance.insuranceProvider',
            'visitInsurance.insuranceTier',
            'visitServices.serviceCatalog',
            'visitServices.department',
            'invoices.items.department',
            'invoices.items.serviceCatalog',
            'triage.triagedBy',
            'triage.department',
            'departmentHistory.department',
            'currentDepartment',
        ]);

        // Insurance info for display
        $insuranceInfo = null;
        if ($visit->visitInsurance) {
            $insuranceInfo = $this->insuranceService->getUsageSummary($visit->visitInsurance);
            $insuranceInfo['provider'] = $visit->visitInsurance->insuranceProvider;
            $insuranceInfo['insurance'] = $visit->visitInsurance;
        }

        $consultationDepartments = Department::active()
            ->where('type', DepartmentType::CONSULTATION->value)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('visits.show', compact('visit', 'insuranceInfo', 'consultationDepartments'));
    }

    public function transition(Request $request, Visit $visit)
    {
        $request->validate([
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = VisitStatus::from($request->status);

        if (! $visit->canTransitionTo($newStatus)) {
            return back()->with('error', "Cannot transition from {$visit->status->label()} to {$newStatus->label()}.");
        }

        $this->visitService->transition($visit, $newStatus, $request->notes);

        return back()->with('success', "Visit status updated to {$newStatus->label()}.");
    }

    public function sendToDepartment(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->visitService->sendToDepartment($visit, (int) $request->department_id, $request->notes);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Patient sent to department and queue entry created.');
    }

    public function patientSearch(Request $request)
    {
        $term = $request->get('q', '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $patients = Patient::search($term)
            ->whereIn('status', ['active', 'inactive', 'deceased'])
            ->select('id', 'patient_number', 'first_name', 'last_name', 'other_names', 'phone', 'status', 'is_deceased')
            ->with('activeAdmission.bed.ward')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                $lastVisit = $p->visits()->latest('visit_date')->value('visit_date');
                $activeAdmission = $p->activeAdmission;

                return [
                    'id' => $p->id,
                    'text' => "{$p->patient_number} — {$p->full_name}",
                    'patient_number' => $p->patient_number,
                    'full_name' => $p->full_name,
                    'phone' => $p->phone,
                    'last_visit_date' => $lastVisit ? Carbon::parse($lastVisit)->format('d M Y') : null,
                    'is_deceased' => (bool) $p->is_deceased,
                    'active_admission' => $activeAdmission ? [
                        'id' => $activeAdmission->id,
                        'admission_number' => $activeAdmission->admission_number,
                        'bed' => $activeAdmission->bed?->bed_number,
                        'ward' => $activeAdmission->bed?->ward?->name,
                    ] : null,
                ];
            });

        return response()->json($patients);
    }

    /**
     * AJAX: Get patient insurances with full validation/coverage data.
     */
    public function patientInsurances(Request $request)
    {
        $patient = Patient::findOrFail($request->patient_id);
        $insurances = $this->insuranceService->getPatientInsurances($patient);
        $resolved = $this->insuranceService->resolveForVisit($patient);

        return response()->json([
            'insurances' => $insurances,
            'default_insurance_id' => $resolved['insurance']?->id,
            'is_fallback' => $resolved['is_fallback'],
        ]);
    }

    /**
     * AJAX: Get services for a department (includes insurance pricing).
     */
    public function departmentServices(Request $request)
    {
        $services = $this->visitService->getServicesForDepartment($request->department_id);

        return response()->json($services->map(fn ($s) => $this->formatServiceForJson($s)));
    }

    /**
     * AJAX: Get doctors for selected services (via specialties).
     */
    public function doctorsForServices(Request $request)
    {
        $serviceIds = $request->input('service_ids', []);
        $doctors = $this->visitService->getDoctorsForServices($serviceIds);

        return response()->json($doctors->map(fn ($d) => [
            'id' => $d->id,
            'name' => 'Dr. '.$d->full_name,
            'specialties' => $d->specialties->pluck('name')->toArray(),
        ]));
    }

    /**
     * AJAX: Get services for a doctor (via specialties).
     */
    public function servicesForDoctor(Request $request)
    {
        $services = $this->visitService->getServicesForDoctor($request->doctor_id);

        return response()->json($services->map(fn ($s) => $this->formatServiceForJson($s)));
    }

    /**
     * AJAX: Get the applicable price for a service given an insurance.
     */
    public function servicePrice(Request $request)
    {
        $request->validate([
            'service_id' => ['required', 'exists:service_catalog,id'],
            'insurance_id' => ['nullable', 'exists:patient_insurances,id'],
        ]);

        $service = ServiceCatalog::with('prices')->findOrFail($request->service_id);

        $insuranceType = null;
        $providerId = null;

        if ($request->insurance_id) {
            $patientIns = PatientInsurance::with('insuranceProvider')
                ->find($request->insurance_id);
            if ($patientIns) {
                $insuranceType = $patientIns->insuranceProvider?->type;
                $providerId = $patientIns->insurance_provider_id;
            }
        }

        $price = $service->getPriceForInsurance($insuranceType, $providerId);

        return response()->json([
            'price' => $price,
            'formatted_price' => '₵'.number_format($price, 2),
        ]);
    }

    /**
     * Format a ServiceCatalog model for JSON (includes insurance pricing).
     */
    private function formatServiceForJson(ServiceCatalog $s): array
    {
        // Build per-type default prices map
        $typePrices = [];
        // Build per-provider overrides map: [provider_id => [type => price]]
        $providerPrices = [];

        foreach ($s->prices as $sp) {
            if ($sp->insurance_provider_id === null) {
                $typePrices[$sp->insurance_type] = (float) $sp->price;
            } else {
                $providerPrices[$sp->insurance_provider_id][$sp->insurance_type] = (float) $sp->price;
            }
        }

        $departmentType = $s->department?->type ?? $s->department_type;

        return [
            'id' => $s->id,
            'name' => $s->name,
            'code' => $s->code,
            'category' => $s->category,
            'price' => (float) $s->price,
            'formatted_price' => $s->formatted_price,
            'base_price' => (float) $s->price,
            'department_id' => $s->department_id,
            'department_type' => $departmentType instanceof DepartmentType ? $departmentType->value : (string) $departmentType,
            'type_prices' => $typePrices,
            'provider_prices' => $providerPrices,
        ];
    }

    /**
     * Auto-create a single invoice for the visit covering all attached
     * services. Triggered on visit submission so billing is ready immediately.
     * Failures are logged but do not abort the visit creation.
     */
    /**
     * @deprecated Removed in May 2026 — was a source of double billing.
     * Selected services are billed exactly once by VisitService::attachServices()
     * via BillingService::addItemToVisitInvoice(). Do not reintroduce.
     */
    private function autoCreateInvoiceForVisit(Visit $visit): void
    {
        // Intentionally a no-op. Kept only to avoid breaking any stray call sites.
    }

    private function resolveBillingType(Visit $visit): string
    {
        $provider = $visit->visitInsurance?->insuranceProvider;
        if (! $provider || $provider->is_default) {
            return BillingType::CASH->value;
        }

        return BillingType::INSURANCE->value;
    }
}
