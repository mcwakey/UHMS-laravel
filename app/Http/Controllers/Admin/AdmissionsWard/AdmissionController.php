<?php

namespace App\Http\Controllers\Admin\AdmissionsWard;

use App\Enums\LogModule;
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
use App\Services\AdmissionMedicationBoardService;
use App\Services\ActivityLogService;
use App\Services\Admissions\AdmissionCareOverviewService;
use App\Services\Admissions\AdmissionDischargeReadinessService;
use App\Services\Admissions\AdmissionDischargeSummaryPrefillService;
use App\Services\Admissions\AdmissionExtensionService;
use App\Services\Admissions\AdmissionRequestService;
use App\Services\AdmissionService;
use App\Services\ConsultationSummaryService;
use App\Services\ServicePriceResolver;
use App\Services\VisitService;
use App\Services\WardService;
use App\Services\WorkspaceRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        private AdmissionDischargeReadinessService $dischargeReadiness,
        private AdmissionDischargeSummaryPrefillService $dischargeSummaryPrefill,
        private WorkspaceRouteResolver $workspaceRoutes,
        private ServicePriceResolver $priceResolver,
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
        $filters = array_merge(array_filter([
            'status' => $request->route('status'),
        ]), $request->all());
        $admissions = $this->admissionService->list($filters);
        $wards = $this->wardService->activeWards();
        $stats = $this->admissionService->getStats();

        return view('admissions.index', compact('admissions', 'wards', 'stats', 'filters'));
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
                'medicalRecord.diagnoses.icdCodeEntry',
                'medicalRecords.diagnoses.icdCodeEntry',
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
            'medicalRecord.diagnoses.icdCodeEntry',
            'medicalRecords.diagnoses.icdCodeEntry',
        ])
            ->where('status', VisitStatus::ADMITTING)
            ->orderByDesc('created_at')
            ->get();

        $availableBeds = $this->wardService->getAvailableBeds();
        if ($preselectedAdmissionRequest?->reservedBed && ! $availableBeds->contains('id', $preselectedAdmissionRequest->reserved_bed_id)) {
            $availableBeds->push($preselectedAdmissionRequest->reservedBed);
        }
        $wards = $this->wardService->activeWards();

        // Services for admission/consumable fee mapping
        $services = ServiceCatalog::with('prices')->where('is_active', true)->orderBy('name')->get();

        // Load saved defaults from settings (can be overridden by ?bed_id param but not service defaults)
        $defaultAdmissionFeeServiceId = (int) Setting::getValue('ward', 'admission_fee_service_id', 0) ?: null;
        $defaultDetentionFeeServiceId = (int) Setting::getValue('ward', 'detention_fee_service_id', 0) ?: null;
        $defaultConsumableFeeServiceId = (int) Setting::getValue('ward', 'consumable_fee_service_id', 0) ?: null;
        $defaultAdmittingDiagnosis = $this->admittingDiagnosisFromVisit($preselectedVisit)
            ?: ($preselectedAdmissionRequest->provisional_diagnosis ?? '');
        $admittingDiagnosisByVisit = $admittingVisits
            ->mapWithKeys(fn (Visit $visit) => [$visit->id => $this->admittingDiagnosisFromVisit($visit)])
            ->all();
        $servicePreviewPrices = $preselectedVisit
            ? $services->mapWithKeys(fn (ServiceCatalog $service) => [
                $service->id => (float) ($this->priceResolver->resolveForVisit($service, $preselectedVisit)['selected_price'] ?? $service->price),
            ])->all()
            : [];
        $canEditAdmissionBillingAmounts = $request->user()?->can(StoreAdmissionRequest::EDIT_BILLING_AMOUNTS_PERMISSION) ?? false;

        return view('admissions.create', compact(
            'preselectedVisit', 'preselectedAdmissionRequest', 'preselectedBedId', 'admittingVisits', 'availableBeds', 'wards', 'services',
            'defaultAdmissionFeeServiceId', 'defaultDetentionFeeServiceId', 'defaultConsumableFeeServiceId',
            'defaultAdmittingDiagnosis', 'admittingDiagnosisByVisit', 'servicePreviewPrices', 'canEditAdmissionBillingAmounts'
        ));
    }

    private function admittingDiagnosisFromVisit(?Visit $visit): string
    {
        if (! $visit) {
            return '';
        }

        $visit->loadMissing(['medicalRecord.diagnoses.icdCodeEntry', 'medicalRecords.diagnoses.icdCodeEntry']);

        $records = $visit->medicalRecords?->isNotEmpty()
            ? $visit->medicalRecords
            : collect($visit->medicalRecord ? [$visit->medicalRecord] : []);

        $diagnoses = $records
            ->flatMap(fn ($record) => $record->diagnoses ?? collect())
            ->filter(fn ($diagnosis) => filled($diagnosis->description))
            ->sortByDesc(fn ($diagnosis) => (int) $diagnosis->is_primary)
            ->unique(fn ($diagnosis) => mb_strtolower(trim((string) $diagnosis->description)))
            ->values();

        if ($diagnoses->isEmpty()) {
            return '';
        }

        $primary = $diagnoses->firstWhere('is_primary', true) ?? $diagnoses->first();
        $lines = ['Primary diagnosis: ' . $this->diagnosisDisplay($primary)];

        foreach ($diagnoses->reject(fn ($diagnosis) => $diagnosis->is($primary))->take(4) as $diagnosis) {
            $lines[] = 'Other diagnosis: ' . $this->diagnosisDisplay($diagnosis);
        }

        return implode("\n", $lines);
    }

    private function diagnosisDisplay($diagnosis): string
    {
        $code = $diagnosis->icdCodeEntry?->code ?? $diagnosis->icd_code;

        return trim(($code ? $code . ' - ' : '') . $diagnosis->description);
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
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
            ->with('success', __('messages.admissions.admitted', ['number' => $admission->admission_number]));
    }

    public function show(Admission $admission)
    {
        $admission->load([
            'patient',
            'bed.ward.department',
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
            'visit.latestInvoice.items.department',
            'visit.latestInvoice.items.serviceCatalog.department',
            'visit.medicalRecord.complaints',
            'visit.medicalRecord.historiesOfPresentingComplaint',
            'visit.medicalRecord.physicalExaminations',
            'visit.medicalRecord.diagnoses.icdCodeEntry',
            'visit.medicalRecord.investigations',
            'visit.medicalRecord.treatments',
            'visit.medicalRecord.prescriptions.items.drug',
            'visit.medicalRecord.consultationRoute.specialtyEntries',
            'visit.medicalRecord.tasks.assignedUser',
            'visit.medicalRecord.doctor',
            'wardRounds.recordedBy',
            'medicationOrders.drug',
            'medicationOrders.product',
            'medicationOrders.frequency',
            'medicationAdministrations.medicationOrder.drug',
            'clinicalTasks',
            'nursingNotes.nurse',
            'nursingNotes.createdBy',
            'nursingNotes.updatedBy',
            'nursingTasks.assignedTo',
            'nursingTasks.createdBy',
            'nursingTasks.completedBy',
            'serviceRenderings.service',
            'dischargePlanningStartedBy',
            'dischargeClearances.clearedBy',
            'dischargeClearances.revokedBy',
            'dischargeSummaryRecord.preparedBy',
            'dischargeSummaryRecord.approvedBy',
        ]);

        $admissionDepartment = $admission->bed?->ward?->department;
        $admissionBillingServices = ServiceCatalog::query()
            ->where('is_active', true)
            ->where('is_billable', true)
            ->where(function ($query) use ($admissionDepartment) {
                if ($admissionDepartment) {
                    $query->where('department_id', $admissionDepartment->id)
                        ->orWhere('department_type', $admissionDepartment->type?->value);
                }
            })
            ->orderBy('name')
            ->get();
        $availableTransferBeds = $this->wardService->getAvailableBeds();
        $nursingAssignableUsers = User::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(100)
            ->get(['id', 'first_name', 'last_name']);

        $medicalRecord = $admission->visit->medicalRecord;
        $consultationSummary = $this->summaryService->forRecord($medicalRecord);
        $medicationBoard = $this->medicationBoardService->forAdmission($admission);
        $careOverview = $this->careOverviewService->forAdmission($admission, $medicationBoard);
        $dischargeReadiness = $this->dischargeReadiness->forAdmission($admission, $medicationBoard, true, $careOverview);
        $dischargeSummaryPrefill = $this->dischargeSummaryPrefill->forAdmission($admission);

        return view('admissions.show', compact('admission', 'admissionBillingServices', 'medicalRecord', 'consultationSummary', 'medicationBoard', 'careOverview', 'dischargeReadiness', 'dischargeSummaryPrefill', 'availableTransferBeds', 'nursingAssignableUsers'));
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
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
            ->with('success', __('messages.admissions.discharged'));
    }

    public function extend(Request $request, Admission $admission, AdmissionExtensionService $extensions)
    {
        $extensions->assertWithinExtensionWindow($admission);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $admission = $extensions->extend($admission, $request->user(), $data['reason']);

        return redirect()
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
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
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
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
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-vitals')
            ->with('success', __('messages.admissions.vitals_recorded'));
    }

    public function updateVital(Request $request, Admission $admission, Vital $vital)
    {
        $belongsToAdmission = (int) $vital->admission_id === (int) $admission->id;
        $belongsToVisit = (int) $vital->visit_id === (int) $admission->visit_id
            && (int) $vital->patient_id === (int) $admission->patient_id;

        abort_unless($belongsToAdmission || $belongsToVisit, 404);

        $data = $request->validate([
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

        $trackedFields = [
            'blood_pressure_systolic',
            'blood_pressure_diastolic',
            'heart_rate',
            'temperature',
            'respiratory_rate',
            'spo2',
            'weight',
            'blood_sugar',
            'notes',
            'recorded_at',
        ];
        $oldValues = $this->vitalAuditValues($vital, $trackedFields);

        $vital->update(array_merge($data, [
            'admission_id' => $admission->id,
            'visit_id' => $admission->visit_id,
            'patient_id' => $admission->patient_id,
            'recorded_at' => $data['recorded_at'] ?? $vital->recorded_at ?? now(),
        ]));
        $vital->refresh();

        $newValues = $this->vitalAuditValues($vital, $trackedFields);
        [$changedOldValues, $changedNewValues] = $this->vitalAuditChanges($oldValues, $newValues);

        if (! empty($changedNewValues)) {
            app(ActivityLogService::class)->log(
                LogModule::ADMISSION,
                'VITALS_UPDATED',
                [
                    'old_values' => $changedOldValues,
                    'new_values' => $changedNewValues,
                    'patient_id' => $admission->patient_id,
                    'visit_id' => $admission->visit_id,
                    'admission_id' => $admission->id,
                    'source_type' => 'vital',
                    'source_id' => $vital->id,
                    'metadata' => [
                        'vital_id' => $vital->id,
                        'linked_from_visit' => ! $belongsToAdmission && $belongsToVisit,
                    ],
                    'description' => 'Admission vitals updated',
                    'causer' => $request->user(),
                ],
                $vital,
                'Admission vitals updated'
            );
        }

        return redirect()
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-vitals')
            ->with('success', __('messages.admissions.vitals_updated'));
    }

    public function storeService(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $admission->loadMissing('bed.ward.department');
        $department = $admission->bed?->ward?->department;
        $service = ServiceCatalog::query()
            ->whereKey($data['service_catalog_id'])
            ->where('is_active', true)
            ->where('is_billable', true)
            ->where(function ($query) use ($department) {
                if ($department) {
                    $query->where('department_id', $department->id)
                        ->orWhere('department_type', $department->type?->value);
                }
            })
            ->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'service_catalog_id' => __('admissions.service_not_available_for_admission'),
            ]);
        }

        $qty = $data['quantity'] ?? 1;

        $this->visitService->attachServices($admission->visit, [[
            'service_catalog_id' => $service->id,
            'quantity' => $qty,
            'notes' => $data['notes'] ?? null,
        ]]);

        // VisitService::attachServices already creates the invoice line item via
        // BillingService::addItemToVisitInvoice and recalculates invoice totals.
        // No additional bookkeeping is needed here.

        return redirect()
            ->route($this->workspaceRoutes->routeName('admin.admissions.show'), $admission)
            ->withFragment('tab-billing')
            ->with('success', __('messages.admissions.charge_added'));
    }

    private function vitalAuditValues(Vital $vital, array $fields): array
    {
        return collect($fields)
            ->mapWithKeys(function (string $field) use ($vital) {
                $value = $vital->{$field};
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                return [$field => $value];
            })
            ->all();
    }

    private function vitalAuditChanges(array $oldValues, array $newValues): array
    {
        $old = [];
        $new = [];

        foreach ($newValues as $field => $value) {
            if (($oldValues[$field] ?? null) === $value) {
                continue;
            }

            $old[$field] = $oldValues[$field] ?? null;
            $new[$field] = $value;
        }

        return [$old, $new];
    }
}
