<?php

namespace App\Http\Controllers\Admin\Patients;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkPatientDeceasedRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\CountryRegion;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\Patient;
use App\Services\PatientPrivacyService;
use App\Services\PatientService;
use App\Services\VisitService;
use App\Services\WorkspaceRouteResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private WorkspaceRouteResolver $workspaceRoutes,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->all();
        $filters['per_page'] = $this->normalizePerPage($filters['per_page'] ?? null);
        $dateRange = trim((string) ($filters['date_range'] ?? ''));
        if ($dateRange !== '') {
            $parts = preg_split('/\s+(?:to|-)\s+/', $dateRange);
            $filters['visit_from'] = $this->normalizeDateValue($parts[0] ?? null);
            $filters['visit_to'] = $this->normalizeDateValue($parts[1] ?? ($parts[0] ?? null));
        } else {
            $filters['visit_from'] = $this->normalizeDateValue($filters['visit_from'] ?? null);
            $filters['visit_to'] = $this->normalizeDateValue($filters['visit_to'] ?? null);
        }

        if (! empty($filters['visit_from']) && empty($filters['visit_to'])) {
            $filters['visit_to'] = $filters['visit_from'];
        }

        if (! empty($filters['visit_to']) && empty($filters['visit_from'])) {
            $filters['visit_from'] = $filters['visit_to'];
        }

        if (! empty($filters['visit_from']) && ! empty($filters['visit_to'])) {
            if (Carbon::parse($filters['visit_from'])->gt(Carbon::parse($filters['visit_to']))) {
                [$filters['visit_from'], $filters['visit_to']] = [$filters['visit_to'], $filters['visit_from']];
            }

            $filters['date_range'] = $filters['visit_from'].' to '.$filters['visit_to'];
        }

        $patients = $this->patientService->list($filters);

        $insuranceProviders = InsuranceProvider::where('is_active', true)
            ->where('is_default', false)
            ->with('insuranceType')
            ->orderBy('name')
            ->get();

        return view('patients.index', compact('patients', 'insuranceProviders', 'filters'));
    }

    private function normalizeDateValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePerPage(mixed $value): int|string
    {
        if (is_string($value) && strtolower($value) === 'all') {
            return 'all';
        }

        $perPage = (int) $value;
        $allowed = [10, 25, 50, 100];

        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }

    public function create()
    {
        $insuranceProviders = InsuranceProvider::where('is_active', true)
            ->where('is_default', false)
            ->orderBy('name')
            ->get();
        $regions = $this->setupCountryRegions();
        $occupations = config('patient_reference.occupations', []);
        $countrySettings = $this->setupCountrySettings();

        return view('patients.create', compact('insuranceProviders', 'regions', 'occupations', 'countrySettings'));
    }

    public function store(StorePatientRequest $request)
    {
        $patient = $this->patientService->create($request->validated());

        // Create emergency contacts submitted inline during registration
        foreach ($request->input('emergency_contacts', []) as $index => $ec) {
            if (empty($ec['name'])) {
                continue;
            }
            $patient->emergencyContacts()->create([
                'name'            => $ec['name'],
                'phone'           => $ec['phone'],
                'phone_secondary' => $ec['phone_secondary'] ?? null,
                'relationship'    => $ec['relationship'] ?? null,
                'is_primary'      => $index === 0,
            ]);
        }

        // Create insurances submitted inline during registration
        foreach (($request->input('insurances') ?? []) as $index => $ins) {
            if (empty($ins['provider_id'])) {
                continue;
            }
            $tierId = $ins['insurance_tier_id'] ?? null;
            if ($tierId) {
                $tierId = InsuranceTier::where('id', $tierId)
                    ->where('insurance_provider_id', $ins['provider_id'])
                    ->value('id');
            }
            $tierId ??= InsuranceTier::where('insurance_provider_id', $ins['provider_id'])
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');

            $patient->insurances()->create([
                'insurance_provider_id' => $ins['provider_id'],
                'insurance_tier_id'      => $tierId,
                'member_type'            => 'holder',
                'membership_number'     => ($ins['membership_number'] ?? null) ?: null,
                'policy_number'         => ($ins['policy_number'] ?? null) ?: null,
                'ccc_code'              => ($ins['ccc_code'] ?? null) ?: null,
                'expiry_date'           => ($ins['expiry_date'] ?? null) ?: null,
                'is_primary'            => $index === 0,
                'is_active'             => true,
            ]);
        }

        return redirect()
            ->to($this->workspaceRoutes->patientShow($patient))
            ->with('success', __('messages.patients.registered', ['number' => $patient->patient_number]));
    }

    public function show(Patient $patient, Request $request)
    {
        // AJAX: Return patient insurances as JSON
        if ($request->ajax() && $request->get('format') === 'insurances') {
            $privacy = app(PatientPrivacyService::class);
            if ($patient->isMerged()) {
                $patient = $patient->getFinalPatient();
            }

            $patient->load('insurances.insuranceProvider');
            return response()->json([
                'insurances' => $patient->insurances
                    ->where('is_active', true)
                    ->map(fn($ins) => [
                        'id' => $ins->id,
                        'provider_name' => $ins->insuranceProvider->name,
                        'membership_number' => $privacy->display('membership_number', $ins->membership_number),
                        'policy_number' => $privacy->display('policy_number', $ins->policy_number),
                        'ccc_code' => $privacy->display('ccc_code', $ins->ccc_code),
                        'is_primary' => $ins->is_primary,
                        'is_expired' => $ins->is_expired,
                    ])->values(),
            ]);
        }

        if ($patient->isMerged()) {
            $patient->load(['mergedToPatient', 'mergedBy', 'aliases']);

            return view('patients.merged', compact('patient'));
        }

        $patient->load([
            'registeredBy',
            'insurances.insuranceProvider',
            'insurances.insuranceTier',
            'emergencyContacts',
            'markedDeceasedBy',
            'activePrivacyDirectives.createdBy',
        ]);

        // All activity connected to this patient across EVERY module (not only
        // actions whose subject is the Patient row) + any merged-folder history.
        $activityLogs = app(\App\Services\ActivityLogService::class)
            ->getPatientTimeline($patient)
            ->take(100)
            ->get();

        // Load visits separately to avoid window-function queries on older MariaDB
        $visits = \App\Models\Visit::where('patient_id', $patient->id)
            ->with([
                'currentDepartment',
                'activeConsultationRoute.doctor',
                'pendingConsultationRoutes.doctor',
                'invoices.items',
                'visitServices.serviceCatalog',
                'visitServices.department',
            ])
            ->latest('visit_date')
            ->take(20)
            ->get();
        $patient->setRelation('visits', $visits);

        $insuranceProviders = InsuranceProvider::where('is_active', true)
            ->with('insuranceType')
            ->orderBy('name')
            ->get();
        $upcomingVisits = app(VisitService::class)->upcomingForPatient($patient->id);
        $upcomingAppointments = \App\Models\Appointment::with(['department', 'doctor', 'services'])
            ->where('patient_id', $patient->id)
            ->upcoming()
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->take(10)
            ->get();

        app(PatientPrivacyService::class)->auditPatientProfileView($patient);

        $activeBreakGlass = \App\Models\PatientPrivacyOverride::active()
            ->where('patient_id', $patient->id)
            ->where('user_id', $request->user()->id)
            ->latest('expires_at')
            ->first();

        // Financial risk (Payment Timing Policy Phase 5) — sensitive administrative
        // data. Loaded ONLY when the viewer is authorised, so it never reaches the
        // page/props for anyone else. Not a payment decision.
        $financialRisk = null;
        $financialRiskHistory = collect();
        if ($request->user()?->can('patients.financial_risk.view')) {
            $financialRisk = app(\App\Services\Billing\PatientFinancialRiskService::class)->currentFor($patient);
            $financialRisk?->load(['setter', 'reviewer', 'suspender', 'clearer']);
            if ($request->user()->can('patients.financial_risk.history')) {
                $financialRiskHistory = $patient->financialRiskHistory()->with('performer')->take(50)->get();
            }
        }

        return view('patients.show', compact('patient', 'insuranceProviders', 'upcomingVisits', 'upcomingAppointments', 'activityLogs', 'activeBreakGlass', 'financialRisk', 'financialRiskHistory'));
    }

    public function edit(Patient $patient)
    {
        $regions = $this->setupCountryRegions();
        $occupations = config('patient_reference.occupations', []);
        $countrySettings = $this->setupCountrySettings();

        return view('patients.edit', compact('patient', 'regions', 'occupations', 'countrySettings'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $this->patientService->update($patient, $request->validated());

        return redirect()
            ->to($this->workspaceRoutes->patientShow($patient))
            ->with('success', __('messages.patients.updated'));
    }

    public function updateMedicalSummary(Request $request, Patient $patient)
    {
        abort_unless(
            ($request->user()?->can('patients.edit') || $request->user()?->can('consultation.view_patient'))
            && app(PatientPrivacyService::class)->canEdit('allergies', $request->user())
            && app(PatientPrivacyService::class)->canEdit('chronic_conditions', $request->user()),
            403
        );

        $validated = $request->validate([
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->patientService->updateMedicalSummary($patient, $validated);

        return back()->with('success', __('messages.patients.medical_summary_updated'));
    }

    public function toggleStatus(Patient $patient)
    {
        if ($patient->status === 'deceased') {
            return back()->with('error', __('messages.patients.cannot_change_deceased_status'));
        }

        $this->patientService->toggleStatus($patient);

        return back()->with('success', __('messages.patients.status_changed', ['status' => $patient->status]));
    }

    public function markDeceased(MarkPatientDeceasedRequest $request, Patient $patient)
    {
        if ($patient->is_deceased) {
            return back()->with('error', __('messages.patients.already_deceased'));
        }

        $this->patientService->markDeceased($patient, $request->validated());

        return back()->with('success', __('messages.patients.marked_deceased', ['name' => $patient->full_name]));
    }

    private function setupCountryRegions()
    {
        return CountryRegion::active()
            ->whereHas('country', fn ($query) => $query
                ->active()
                ->where('iso2', config('patient_reference.setup_country_code', 'GH')))
            ->with(['cities' => fn ($query) => $query
                ->active()
                ->with(['towns' => fn ($townQuery) => $townQuery->active()->orderBy('name')])
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function setupCountrySettings(): array
    {
        $code = config('patient_reference.setup_country_code', 'GH');
        $countries = config('patient_reference.countries', []);

        return $countries[$code] ?? $countries['GH'] ?? ['iso2' => $code];
    }
}
