<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DischargeRequest;
use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\ServiceCatalog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vital;
use App\Models\Ward;
use App\Services\AdmissionMedicationBoardService;
use App\Services\Admissions\AdmissionCareOverviewService;
use App\Services\Admissions\AdmissionDischargeReadinessService;
use App\Services\Admissions\AdmissionExtensionService;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\AdmissionService;
use App\Services\ConsultationSummaryService;
use App\Services\VisitService;
use App\Services\WardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdmissionController extends Controller
{
    public function __construct(
        private AdmissionService $admissionService,
        private WardService $wardService,
        private VisitService $visitService,
        private ConsultationSummaryService $summaryService,
        private AdmissionMedicationBoardService $medicationBoardService,
        private AdmissionRequestService $admissionRequests,
        private AdmissionCareOverviewService $careOverviewService,
        private AdmissionDischargeReadinessService $dischargeReadiness
    ) {}

    public function admissionRequests(Request $request)
    {
        $query = Visit::with([
            'patient',
            'department',
            'activeConsultationRoute.doctor',
        ])->where('status', VisitStatus::ADMITTING->value);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $visits = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('admissions.requests', [
            'visits' => $visits,
            'searchQuery' => $request->input('search', ''),
            'totalPending' => Visit::where('status', VisitStatus::ADMITTING->value)->count(),
        ]);
    }

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
        $admissionRequestId = $request->query('admission_request_id');
        $preselectedBedId = (int) $request->query('bed_id', 0) ?: null;
        $preselectedVisit = null;
        $preselectedAdmissionRequest = null;

        if ($admissionRequestId) {
            $preselectedAdmissionRequest = AdmissionRequest::with([
                'patient.insurances.insuranceProvider',
                'patient.insurances.insuranceTier',
                'visit.visitInsurance.insuranceProvider',
                'visit.visitInsurance.insuranceTier',
                'reservedBed.ward',
            ])->findOrFail($admissionRequestId);

            $visitId = $preselectedAdmissionRequest->visit_id;
            $preselectedBedId = $preselectedBedId ?: $preselectedAdmissionRequest->reserved_bed_id;
        }

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
        if ($preselectedAdmissionRequest?->reservedBed && ! $availableBeds->contains('id', $preselectedAdmissionRequest->reserved_bed_id)) {
            $availableBeds->push($preselectedAdmissionRequest->reservedBed);
        }
        $wards = Ward::active()->orderBy('name')->get();

        // Services for admission/consumable fee mapping
        $services = ServiceCatalog::where('is_active', true)->orderBy('name')->get();

        // Load saved defaults from settings (can be overridden by ?bed_id param but not service defaults)
        $defaultAdmissionFeeServiceId = (int) Setting::getValue('ward', 'admission_fee_service_id', 0) ?: null;
        $defaultDetentionFeeServiceId = (int) Setting::getValue('ward', 'detention_fee_service_id', 0) ?: null;
        $defaultConsumableFeeServiceId = (int) Setting::getValue('ward', 'consumable_fee_service_id', 0) ?: null;

        return view('admissions.create', compact(
            'preselectedVisit', 'preselectedAdmissionRequest', 'preselectedBedId', 'admittingVisits', 'availableBeds', 'wards', 'services',
            'defaultAdmissionFeeServiceId', 'defaultDetentionFeeServiceId', 'defaultConsumableFeeServiceId'
        ));
    }

    public function store(StoreAdmissionRequest $request)
    {
        $data = $request->validated();
        if (! empty($data['admission_request_id'])) {
            $admissionRequest = AdmissionRequest::findOrFail($data['admission_request_id']);
            $admission = $this->admissionRequests->convertToAdmission($admissionRequest, $data, $request->user());
        } else {
            $admission = $this->admissionService->admit($data);
        }

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('success', __('messages.admissions.admitted', ['number' => $admission->admission_number]));
    }

    public function show(Admission $admission)
    {
        $admission->load([
            'patient',
            'bed.ward',
            'admissionRequest.requestedBy',
            'admissionRequest.acceptedBy',
            'admissionRequest.reservedBed.ward',
            'bedReservations.reservedBy',
            'locationHistories.fromWard',
            'locationHistories.fromBed',
            'locationHistories.toWard',
            'locationHistories.toBed',
            'locationHistories.movedBy',
            'admittedBy',
            'dischargedBy',
            'visit.visitServices.serviceCatalog',
            'visit.visitInsurance.insuranceProvider',
            'visit.visitInsurance.insuranceTier',
            'visit.vitals.recordedBy',
            'visit.latestInvoice.items',
            'visit.medicalRecord.complaints',
            'visit.medicalRecord.diagnoses.icdCodeEntry',
            'visit.medicalRecord.treatments',
            'visit.medicalRecord.prescriptions.items.drug',
            'visit.medicalRecord.tasks.assignedUser',
            'visit.medicalRecord.doctor',
            'wardRounds.recordedBy',
            'nursingNotes.nurse',
            'nursingNotes.createdBy',
            'nursingNotes.updatedBy',
            'nursingTasks.assignedTo',
            'nursingTasks.createdBy',
            'nursingTasks.completedBy',
            'dischargePlanningStartedBy',
            'dischargeClearances.clearedBy',
            'dischargeClearances.revokedBy',
            'dischargeSummaryRecord.preparedBy',
            'dischargeSummaryRecord.approvedBy',
        ]);

        $services = ServiceCatalog::where('is_active', true)->orderBy('name')->get();
        $availableTransferBeds = $this->wardService->getAvailableBeds();
        $nursingAssignableUsers = User::query()->orderBy('name')->limit(100)->get(['id', 'name']);

        $medicalRecord = $admission->visit->medicalRecord;
        $consultationSummary = $this->summaryService->forRecord($medicalRecord);
        $medicationBoard = $this->medicationBoardService->forAdmission($admission);
        $careOverview = $this->careOverviewService->forAdmission($admission, $medicationBoard);
        $dischargeReadiness = $this->dischargeReadiness->forAdmission($admission, $medicationBoard);

        return view('admissions.show', compact('admission', 'services', 'medicalRecord', 'consultationSummary', 'medicationBoard', 'careOverview', 'dischargeReadiness', 'availableTransferBeds', 'nursingAssignableUsers'));
    }

    public function discharge(Admission $admission)
    {
        $admission->load([
            'patient',
            'bed.ward',
            'admittedBy',
            'wardRounds',
            'nursingTasks',
            'visit.latestInvoice.items',
            'visit.latestInvoice.payments',
            'visit.vitals',
            'dischargePlanningStartedBy',
            'dischargeClearances.clearedBy',
            'dischargeSummaryRecord.preparedBy',
            'dischargeSummaryRecord.approvedBy',
        ]);
        $medicationBoard = $this->medicationBoardService->forAdmission($admission);
        $dischargeReadiness = $this->dischargeReadiness->forAdmission($admission, $medicationBoard);

        return view('admissions.discharge', compact('admission', 'dischargeReadiness'));
    }

    public function processDischarge(DischargeRequest $request, Admission $admission)
    {
        $medicationBoard = $this->medicationBoardService->forAdmission($admission);
        $this->dischargeReadiness->assertCanDischarge($admission, $medicationBoard);

        $this->admissionService->discharge($admission, $request->validated());

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('success', __('messages.admissions.discharged'));
    }

    public function extend(Request $request, Admission $admission, AdmissionExtensionService $extensions)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $admission = $extensions->extend($admission, $request->user(), $data['reason']);

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('success', __('admissions.admission_extended'));
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
            ->with('success', __('messages.admissions.ward_round_saved'));
    }

    public function storeVital(Request $request, Admission $admission)
    {
        $request->validate([
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:300'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:200'],
            'heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:60'],
            'spo2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'blood_sugar' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        Vital::create(array_merge($request->only([
            'blood_pressure_systolic', 'blood_pressure_diastolic', 'heart_rate',
            'temperature', 'respiratory_rate', 'spo2', 'weight', 'blood_sugar', 'notes',
        ]), [
            'admission_id' => $admission->id,
            'visit_id' => $admission->visit_id,
            'patient_id' => $admission->patient_id,
            'recorded_by' => Auth::id(),
            'recorded_at' => $request->recorded_at ?? now(),
        ]));

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-vitals')
            ->with('success', __('messages.admissions.vitals_recorded'));
    }

    public function storeService(Request $request, Admission $admission)
    {
        $request->validate([
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $qty = $request->quantity ?? 1;

        $this->visitService->attachServices($admission->visit, [[
            'service_catalog_id' => $request->service_catalog_id,
            'quantity' => $qty,
            'notes' => $request->notes,
        ]]);

        // VisitService::attachServices already creates the invoice line item via
        // BillingService::addItemToVisitInvoice and recalculates invoice totals.
        // No additional bookkeeping is needed here.

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->withFragment('tab-billing')
            ->with('success', __('messages.admissions.charge_added'));
    }
}
