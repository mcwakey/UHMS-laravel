<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Services\InsuranceService;
use App\Services\QueueService;
use App\Services\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function __construct(
        protected VisitService $visitService,
        protected InsuranceService $insuranceService,
        protected QueueService $queueService,
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
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        return view('visits.index', compact('visits', 'stats', 'doctors', 'filters'));
    }

    public function create(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();
        $selectedPatient = null;

        if ($request->has('patient_id')) {
            $selectedPatient = Patient::find($request->patient_id);
        }

        return view('visits.create', compact('departments', 'doctors', 'selectedPatient'));
    }

    public function store(StoreVisitRequest $request)
    {
        try {
            $visit = DB::transaction(function () use ($request) {
                $v = $this->visitService->create($request->validated());

                // Attach selected services inside the same transaction
                $services = $request->validated()['services'] ?? [];
                if (!empty($services)) {
                    $this->visitService->attachServices($v, $services);
                }
                // No queue entry at waiting — it is created when pushed to Triage

                return $v;
            });
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to create visit: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', "Visit {$visit->visit_number} created and patient added to queue.");
    }

    public function edit(Visit $visit)
    {
        $visit->load([
            'patient',
            'visitInsurance.insuranceProvider',
            'visitServices.serviceCatalog',
            'assignedDoctor',
        ]);

        $departments = Department::active()->orderBy('name')->get();
        $doctors     = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        return view('visits.edit', compact('visit', 'departments', 'doctors'));
    }

    public function update(UpdateVisitRequest $request, Visit $visit)
    {
        try {
            DB::transaction(function () use ($request, $visit) {
                $data     = $request->validated();
                $services = $data['services'] ?? null;
                unset($data['services']);

                $this->visitService->update($visit, $data);

                if ($services !== null) {
                    // Delete existing service items before re-attaching to avoid duplicates
                    $visit->visitServices()->delete();
                    if (!empty($services)) {
                        $this->visitService->attachServices($visit, $services);
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to update visit: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', "Visit {$visit->visit_number} updated.");
    }


    public function show(Visit $visit)
    {
        $visit->load([
            'patient',
            'assignedDoctor',
            'createdBy',
            'statusLogs.changedBy',
            'queueEntries.department',
            'visitInsurance.insuranceProvider',
            'visitServices.serviceCatalog',
            'visitServices.department',
        ]);

        // Insurance info for display
        $insuranceInfo = null;
        if ($visit->visitInsurance) {
            $insuranceInfo = $this->insuranceService->getUsageSummary($visit->visitInsurance);
            $insuranceInfo['provider'] = $visit->visitInsurance->insuranceProvider;
            $insuranceInfo['insurance'] = $visit->visitInsurance;
        }

        return view('visits.show', compact('visit', 'insuranceInfo'));
    }

    public function transition(Request $request, Visit $visit)
    {
        $request->validate([
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = VisitStatus::from($request->status);

        if (!$visit->canTransitionTo($newStatus)) {
            return back()->with('error', "Cannot transition from {$visit->status->label()} to {$newStatus->label()}.");
        }

        $this->visitService->transition($visit, $newStatus, $request->notes);

        return back()->with('success', "Visit status updated to {$newStatus->label()}.");
    }

    public function sendToDepartment(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'notes'         => ['nullable', 'string', 'max:500'],
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
            ->active()
            ->select('id', 'patient_number', 'first_name', 'last_name', 'other_names', 'phone')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                $lastVisit = $p->visits()->latest('visit_date')->value('visit_date');
                return [
                    'id' => $p->id,
                    'text' => "{$p->patient_number} — {$p->full_name}",
                    'patient_number' => $p->patient_number,
                    'full_name' => $p->full_name,
                    'phone' => $p->phone,
                    'last_visit_date' => $lastVisit ? \Carbon\Carbon::parse($lastVisit)->format('d M Y') : null,
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
            'name' => 'Dr. ' . $d->full_name,
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
            'service_id'    => ['required', 'exists:service_catalog,id'],
            'insurance_id'  => ['nullable', 'exists:patient_insurances,id'],
        ]);

        $service = \App\Models\ServiceCatalog::with('prices')->findOrFail($request->service_id);

        $insuranceType = null;
        $providerId = null;

        if ($request->insurance_id) {
            $patientIns = \App\Models\PatientInsurance::with('insuranceProvider')
                ->find($request->insurance_id);
            if ($patientIns) {
                $insuranceType = $patientIns->insuranceProvider?->type;
                $providerId = $patientIns->insurance_provider_id;
            }
        }

        $price = $service->getPriceForInsurance($insuranceType, $providerId);

        return response()->json([
            'price'           => $price,
            'formatted_price' => '₵' . number_format($price, 2),
        ]);
    }

    /**
     * Format a ServiceCatalog model for JSON (includes insurance pricing).
     */
    private function formatServiceForJson(\App\Models\ServiceCatalog $s): array
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

        return [
            'id'               => $s->id,
            'name'             => $s->name,
            'code'             => $s->code,
            'category'         => $s->category,
            'price'            => (float) $s->price,
            'formatted_price'  => $s->formatted_price,
            'type_prices'      => $typePrices,
            'provider_prices'  => $providerPrices,
        ];
    }
}
