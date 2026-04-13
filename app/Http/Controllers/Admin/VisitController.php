<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Priority;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Department;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Visit;
use App\Services\InsuranceService;
use App\Services\VisitService;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(
        protected VisitService $visitService,
        protected InsuranceService $insuranceService,
    ) {}

    public function index(Request $request)
    {
        $visits = $this->visitService->list($request->all());
        $stats = $this->visitService->todayStats();
        $departments = Department::active()->orderBy('name')->get();
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        return view('visits.index', compact('visits', 'stats', 'departments', 'doctors'));
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
            $visit = $this->visitService->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // Attach selected services if any
        if ($request->has('services') && is_array($request->services)) {
            $this->visitService->attachServices($visit, $request->services);
        }

        return redirect()
            ->route('admin.visits.show', $visit)
            ->with('success', "Visit {$visit->visit_number} created and patient added to queue.");
    }

    public function show(Visit $visit)
    {
        $visit->load([
            'patient',
            'department',
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
            ->map(fn ($p) => [
                'id' => $p->id,
                'text' => "{$p->patient_number} — {$p->full_name}",
                'patient_number' => $p->patient_number,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
            ]);

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
     * AJAX: Get services for a department.
     */
    public function departmentServices(Request $request)
    {
        $services = $this->visitService->getServicesForDepartment($request->department_id);

        return response()->json($services->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'code' => $s->code,
            'category' => $s->category,
            'price' => $s->price,
            'formatted_price' => $s->formatted_price,
            'is_nhis_covered' => $s->is_nhis_covered,
        ]));
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

        return response()->json($services->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'code' => $s->code,
            'category' => $s->category,
            'price' => $s->price,
            'formatted_price' => $s->formatted_price,
            'is_nhis_covered' => $s->is_nhis_covered,
            'department_id' => $s->department_id,
        ]));
    }
}
