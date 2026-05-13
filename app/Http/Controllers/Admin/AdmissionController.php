<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DischargeRequest;
use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Admission;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use App\Models\Vital;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\VisitService;
use App\Services\WardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function __construct(
        private AdmissionService $admissionService,
        private WardService $wardService,
        private VisitService $visitService
    ) {}

    public function index(Request $request)
    {
        $admissions = $this->admissionService->list($request->all());
        $wards = Ward::active()->orderBy('name')->get();
        $stats = $this->admissionService->getStats();

        return view('admissions.index', compact('admissions', 'wards', 'stats'));
    }

    public function create(Request $request)
    {
        $visitId = $request->query('visit_id');
        $preselectedVisit = null;

        if ($visitId) {
            $preselectedVisit = Visit::with([
                'patient.insurances.insuranceProvider',
                'patient.insurances.insuranceTier',
                'visitInsurance.insuranceProvider',
                'visitInsurance.insuranceTier',
            ])
                ->where('id', $visitId)
                ->where('status', VisitStatus::ADMITTING)
                ->first();
        }

        // Visits awaiting admission (with insurance data for JS data attributes)
        $admittingVisits = Visit::with([
            'patient',
            'visitInsurance.insuranceProvider',
            'visitInsurance.insuranceTier',
        ])
            ->where('status', VisitStatus::ADMITTING)
            ->orderByDesc('created_at')
            ->get();

        $availableBeds = $this->wardService->getAvailableBeds();
        $wards = Ward::active()->orderBy('name')->get();

        // Services for admission/consumable fee mapping
        $services = ServiceCatalog::where('is_active', true)->orderBy('name')->get();

        return view('admissions.create', compact(
            'preselectedVisit', 'admittingVisits', 'availableBeds', 'wards', 'services'
        ));
    }

    public function store(StoreAdmissionRequest $request)
    {
        $admission = $this->admissionService->admit($request->validated());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('success', "Patient admitted successfully. Admission #{$admission->admission_number}");
    }

    public function show(Admission $admission)
    {
        $admission->load([
            'patient',
            'bed.ward',
            'admittedBy',
            'dischargedBy',
            'visit.visitServices.serviceCatalog',
            'visit.vitals.recordedBy',
            'visit.latestInvoice.items',
            'visit.medicalRecord.complaints',
            'visit.medicalRecord.diagnoses.icdCodeEntry',
            'visit.medicalRecord.treatments',
            'visit.medicalRecord.prescriptions.items.drug',
            'visit.medicalRecord.tasks.assignedUser',
            'visit.medicalRecord.doctor',
            'wardRounds.recordedBy',
        ]);

        $services = ServiceCatalog::where('is_active', true)->orderBy('name')->get();

        return view('admissions.show', compact('admission', 'services'));
    }

    public function discharge(Admission $admission)
    {
        $admission->load(['patient', 'bed.ward']);

        return view('admissions.discharge', compact('admission'));
    }

    public function processDischarge(DischargeRequest $request, Admission $admission)
    {
        $this->admissionService->discharge($admission, $request->validated());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('success', 'Patient discharged successfully.');
    }

    public function storeRound(Request $request, Admission $admission)
    {
        $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'round_date' => ['nullable', 'date'],
        ]);

        $this->admissionService->addWardRound($admission, $request->only(['notes', 'instructions', 'round_date']));

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('success', 'Ward round recorded successfully.');
    }

    public function storeVital(Request $request, Admission $admission)
    {
        $request->validate([
            'blood_pressure_systolic'  => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'heart_rate'               => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature'              => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate'         => ['nullable', 'integer', 'min:0', 'max:60'],
            'spo2'                     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'weight'                   => ['nullable', 'numeric', 'min:0', 'max:500'],
            'blood_sugar'              => ['nullable', 'numeric', 'min:0'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
            'recorded_at'              => ['nullable', 'date'],
        ]);

        Vital::create(array_merge($request->only([
            'blood_pressure_systolic', 'blood_pressure_diastolic', 'heart_rate',
            'temperature', 'respiratory_rate', 'spo2', 'weight', 'blood_sugar', 'notes',
        ]), [
            'admission_id' => $admission->id,
            'visit_id'     => $admission->visit_id,
            'patient_id'   => $admission->patient_id,
            'recorded_by'  => Auth::id(),
            'recorded_at'  => $request->recorded_at ?? now(),
        ]));

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-vitals')
            ->with('success', 'Vitals recorded.');
    }

    public function storeService(Request $request, Admission $admission)
    {
        $request->validate([
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'quantity'           => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        $qty = $request->quantity ?? 1;

        $this->visitService->attachServices($admission->visit, [[
            'service_catalog_id' => $request->service_catalog_id,
            'quantity'           => $qty,
            'notes'              => $request->notes,
        ]]);

        // VisitService::attachServices already creates the invoice line item via
        // BillingService::addItemToVisitInvoice and recalculates invoice totals.
        // No additional bookkeeping is needed here.

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-billing')
            ->with('success', 'Service charge added.');
    }
}
