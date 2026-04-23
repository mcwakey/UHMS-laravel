<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DischargeRequest;
use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Admission;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\WardService;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function __construct(
        private AdmissionService $admissionService,
        private WardService $wardService
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
            'visit',
            'wardRounds.recordedBy',
        ]);

        return view('admissions.show', compact('admission'));
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
}
