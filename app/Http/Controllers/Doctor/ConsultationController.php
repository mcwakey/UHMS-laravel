<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Diagnosis;
use App\Models\Drug;
use App\Models\Investigation;
use App\Models\LabRequestItem;
use App\Models\PatientProcedure;
use App\Models\Procedure;
use App\Models\ServiceCatalog;
use App\Models\Treatment;
use App\Models\Visit;
use App\Services\ClinicalService;
use App\Services\ConsultationService;
use App\Services\LabService;
use App\Services\MedicalPatternService;
use App\Services\PrescriptionService;
use App\Services\VisitService;
use App\Services\VisitWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function __construct(
        protected ConsultationService $consultationService,
        protected PrescriptionService $prescriptionService,
        protected VisitService $visitService,
        protected MedicalPatternService $patternService,
        protected LabService $labService,
        protected ClinicalService $clinicalService,
    ) {}

    private function shouldReturnJson(Request $request): bool
    {
        return $request->ajax() && ! $request->headers->has('X-Inertia');
    }

    /**
     * Doctor explicitly starts the consultation (now a no-op since triage moves directly to CONSULTING).
     */
    public function startConsultation(Request $request, Visit $visit, VisitWorkflowService $workflow)
    {
        try {
            $workflow->startConsultation($visit, Auth::user());
        } catch (\Throwable $e) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => 'Consultation started.', 'redirect' => route('admin.consultations.show', $visit)]);
        }
        return redirect()->route('admin.consultations.show', $visit)->with('success', 'Consultation started.');
    }

    /**
     * Delete a single LabRequestItem (doctor-side cancel) — disallowed once a result exists.
     */
    public function destroyInvestigationItem(Request $request, LabRequestItem $item)
    {
        if (!$item->isDeletable()) {
            $msg = 'Cannot delete this investigation: a result has already been entered.';
            if ($this->shouldReturnJson($request)) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }
        $item->update(['status' => 'cancelled']);
        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => 'Investigation removed.']);
        }
        return back()->with('success', 'Investigation removed.');
    }

    /**
     * List consultable visits (today's consulting visits).
     */
    public function index(Request $request)
    {
        $filters = $request->all();

        // Default: outpatient visits for today
        if (!$request->hasAny(['search', 'visit_type', 'date_from'])) {
            $filters['visit_type'] = $filters['visit_type'] ?? VisitType::OUTPATIENT->value;
            $filters['date_from'] = $filters['date_from'] ?? today()->toDateString();
            $filters['date_to'] = $filters['date_to'] ?? today()->toDateString();
        }

        $query = Visit::with(['patient', 'assignedDoctor', 'medicalRecord', 'currentDepartment'])
            ->whereIn('status', [
                VisitStatus::WAITING_CONSULTATION->value,
                VisitStatus::CONSULTING->value,
            ])
            // Only show visits that have a PENDING or ACTIVE consultation route
            // pointing at a consultation-type department. This keeps non-consultation
            // visits (lab-only, pharmacy-only, etc.) out of the consultation queue.
            ->whereHas('consultationRoutes', function ($r) {
                $r->whereIn('status', [
                    \App\Models\VisitConsultationRoute::STATUS_PENDING,
                    \App\Models\VisitConsultationRoute::STATUS_ACTIVE,
                ])->whereHas('department', function ($d) {
                    $d->where('type', \App\Enums\DepartmentType::CONSULTATION->value);
                });
            });

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && ! $user->hasAnyRole(['Super Admin', 'Admin']) && $user->department_id) {
            $query->where('current_department_id', $user->department_id);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['visit_type'])) {
            $query->where('visit_type', $filters['visit_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }

        if ($request->boolean('my_patients')) {
            $query->where('assigned_doctor_id', Auth::id());
        }

        $visits = $query->latest()->paginate(15);

        return view('consultations.index', compact('visits', 'filters'));
    }

    /**
     * Show the consultation interface for a visit.
     */
    public function show(Visit $visit)
    {
        // Auto-create the medical record if needed
        $record = $this->consultationService->getOrCreateRecord($visit);
        $data = $this->consultationService->getConsultationData($visit);

        // Load tasks on the record
        if ($data['record']) {
            $data['record']->load(['tasks.assignedUser', 'tasks.creator']);
        }

        // Get recent/popular patterns for the doctor
        $patterns = \App\Models\MedicalPattern::active()
            ->forDoctor(Auth::id())
            ->with('items')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // Lab data
        $labRequests = $this->labService->getVisitLabRequests($visit);
        $labCategories = $this->labService->getActiveCategories();

        // All departments that accept investigation requests (have a result_type set)
        $investigationDepts = $this->labService->getInvestigationDepartments();

        // Doctors for task assignment
        $doctors = \App\Models\User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        // Drugs for prescription dropdown
        $drugs = Drug::where('is_active', true)->orderBy('name')->get(['id', 'name', 'generic_name', 'strength', 'dosage_form', 'unit']);

        $procedures = Procedure::with('department')->active()->orderBy('name')->get();
        $patientProcedures = PatientProcedure::with(['procedure.department', 'performedByUser'])
            ->where('visit_id', $visit->id)
            ->latest('scheduled_date')
            ->get();

        // Theatre / new procedure workflow
        $procedureRequestService = app(\App\Services\ProcedureRequestService::class);
        $procedureRequests = $procedureRequestService->forVisit($visit->id);
        $procedureDepartments = $procedureRequestService->procedureDepartments();

        return view('consultations.show', [
            'visit' => $data['visit'],
            'record' => $data['record'],
            'vitals' => $data['vitals'],
            'history' => $data['history'],
            'patterns' => $patterns,
            'labRequests' => $labRequests,
            'labCategories' => $labCategories,
            'investigationDepts' => $investigationDepts,
            'doctors' => $doctors,
            'drugs' => $drugs,
            'procedures' => $procedures,
            'patientProcedures' => $patientProcedures,
            'procedureRequests' => $procedureRequests,
            'procedureDepartments' => $procedureDepartments,
        ]);
    }

    /**
     * Show patient's full medical history.
     */
    public function history(Visit $visit)
    {
        $history = $this->consultationService->getPatientHistory($visit->patient_id);
        $visit->load('patient');

        return view('consultations.history', compact('visit', 'history'));
    }

    /*
    |--------------------------------------------------------------------------
    | Complaint CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeComplaint(Request $request, Visit $visit)
    {
        $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'duration' => ['nullable', 'string', 'max:191'],
            'severity' => ['nullable', 'in:mild,moderate,severe'],
        ]);

        $record = $this->consultationService->getOrCreateRecord($visit);
        $complaint = $this->consultationService->addComplaint($record, $request->only('description', 'duration', 'severity'));

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'complaint' => $complaint]);
        }

        return back()->with('success', 'Complaint added.');
    }

    public function destroyComplaint(Complaint $complaint)
    {
        $this->consultationService->deleteComplaint($complaint);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Complaint removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Diagnosis CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeDiagnosis(Request $request, Visit $visit)
    {
        $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'icd_code' => ['nullable', 'string', 'max:20'],
            'icd_code_id' => ['nullable', 'exists:icd_codes,id'],
            'type' => ['nullable', 'in:provisional,final'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->consultationService->getOrCreateRecord($visit);
        $diagnosis = $this->consultationService->addDiagnosis($record, $request->only('description', 'icd_code', 'icd_code_id', 'type', 'notes'));

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'diagnosis' => $diagnosis]);
        }

        return back()->with('success', 'Diagnosis added.');
    }

    public function destroyDiagnosis(Diagnosis $diagnosis)
    {
        $this->consultationService->deleteDiagnosis($diagnosis);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Diagnosis removed.');
    }

    /**
     * Toggle a diagnosis type between provisional and final.
     */
    public function updateDiagnosis(Request $request, Diagnosis $diagnosis)
    {
        $request->validate([
            'type' => ['required', 'in:provisional,final'],
        ]);

        $this->consultationService->updateDiagnosis($diagnosis, $request->only('type'));

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'type' => $diagnosis->fresh()->type]);
        }

        return back()->with('success', 'Diagnosis updated.');
    }

    /**
     * Set a diagnosis as the primary one for this record.
     */
    public function setPrimaryDiagnosis(Diagnosis $diagnosis)
    {
        $this->consultationService->setPrimaryDiagnosis($diagnosis);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Primary diagnosis set.');
    }

    /**
     * Return active services for a department (for investigation dept dropdown).
     */
    public function getDepartmentServices(Department $department)
    {
        $services = $department->services()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price']);

        return response()->json($services);
    }

    /**
     * Return investigation metadata for a department (result_type + catalog tests if applicable).
     */
    public function getDepartmentInvestigationInfo(Department $department)
    {
        $resultType = $department->result_type ?? \App\Enums\ResultType::NONE;

        $data = [
            'result_type'  => $resultType->value,
            'uses_catalog' => $resultType->usesTestCatalog(),
            'label'        => $resultType->label(),
            'lab_tests'    => [],
        ];

        if ($resultType->usesTestCatalog()) {
            $data['lab_tests'] = \App\Models\LabTest::with('criteria')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'unit', 'normal_range', 'price'])
                ->toArray();
        }

        return response()->json($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Investigation CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeInvestigation(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id'    => ['nullable', 'exists:departments,id'],
            'service_ids'      => ['nullable', 'array'],
            'service_ids.*'    => ['exists:service_catalog,id'],
            'investigation_type' => ['nullable', 'string', 'max:191'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'urgency'          => ['nullable', 'in:routine,urgent,emergency'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->consultationService->getOrCreateRecord($visit);
        $created = [];
        $labRequest = null;

        if (!empty($request->service_ids)) {
            $services = ServiceCatalog::whereIn('id', $request->service_ids)->get();

            foreach ($services as $service) {
                $created[] = $this->consultationService->addInvestigation($record, [
                    'investigation_type' => $service->name,
                    'description'        => $service->description ?? $service->name,
                    'urgency'            => $request->urgency ?? 'routine',
                    'notes'              => $request->notes,
                ]);
            }

            // ALSO create a LabRequest so the investigation department sees it on their queue.
            $items = $services->map(fn ($s) => [
                'service_id' => $s->id,
                'name'       => $s->name,
                'status'     => 'pending',
            ])->all();

            if (!empty($items)) {
                $labRequest = $this->labService->createRequest($visit, $items, [
                    'target_department_id' => $request->department_id,
                    'clinical_info'        => $request->notes,
                    'urgency'              => $request->urgency ?? 'routine',
                ]);
            }
        } elseif (!empty($request->investigation_type)) {
            $created[] = $this->consultationService->addInvestigation($record, [
                'investigation_type' => $request->investigation_type,
                'description'        => $request->description ?? $request->investigation_type,
                'urgency'            => $request->urgency ?? 'routine',
                'notes'              => $request->notes,
            ]);

            // Create a free-text LabRequest for non-catalogue requests
            $labRequest = $this->labService->createRequest($visit, [$request->investigation_type], [
                'target_department_id' => $request->department_id,
                'clinical_info'        => $request->description ?? $request->notes,
                'urgency'              => $request->urgency ?? 'routine',
            ]);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success'        => true,
                'investigations' => $created,
                'count'          => count($created),
                'lab_request'    => $labRequest?->only(['id', 'request_number', 'status']),
            ]);
        }

        $msg = count($created) . ' investigation(s) added';
        if ($labRequest) {
            $msg .= " — request {$labRequest->request_number} sent to investigation department.";
        } else {
            $msg .= '.';
        }

        return back()->with('success', $msg);
    }

    public function destroyInvestigation(Investigation $investigation)
    {
        $this->consultationService->deleteInvestigation($investigation);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Investigation removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Treatment CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeTreatment(Request $request, Visit $visit)
    {
        $request->validate([
            'type' => ['required', 'in:medication,procedure,referral,advice'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $record = $this->consultationService->getOrCreateRecord($visit);
        $treatment = $this->consultationService->addTreatment($record, $request->only('type', 'description'));

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'treatment' => $treatment]);
        }

        return back()->with('success', 'Treatment added.');
    }

    public function destroyTreatment(Treatment $treatment)
    {
        $this->consultationService->deleteTreatment($treatment);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Treatment removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Prescription
    |--------------------------------------------------------------------------
    */

    public function storePrescription(StorePrescriptionRequest $request, Visit $visit)
    {
        $record = $this->consultationService->getOrCreateRecord($visit);
        $prescription = $this->prescriptionService->create($record, $request->validated());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'prescription' => $prescription->load('items')]);
        }

        return redirect()
            ->route('admin.consultations.show', $visit)
            ->withFragment('prescriptions-section')
            ->with('success', "Prescription {$prescription->prescription_number} created and sent to pharmacy.");
    }

    public function destroyPrescription(\App\Models\Prescription $prescription)
    {
        // Only allow deletion of pending/active prescriptions
        $allowedStatuses = ['pending', 'active'];
        if (!in_array($prescription->status->value, $allowedStatuses)) {
            if ($this->shouldReturnJson(request())) {
                return response()->json(['success' => false, 'message' => 'Cannot delete a dispensed or cancelled prescription.'], 422);
            }
            return back()->with('error', 'Cannot delete a dispensed or cancelled prescription.');
        }

        $prescription->items()->delete();
        $prescription->delete();

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Prescription deleted.');
    }

    public function storeProcedureRequest(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'department_id'      => ['required', 'exists:departments,id'],
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'procedure_id'       => ['nullable', 'exists:procedures,id'],
            'priority'           => ['required', 'in:routine,urgent,emergency'],
            'indication'         => ['required', 'string', 'max:2000'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
        ]);
        $data['visit_id'] = $visit->id;

        try {
            $procedureRequest = app(\App\Services\ProcedureRequestService::class)
                ->requestProcedure($data, \Illuminate\Support\Facades\Auth::user());
        } catch (\Throwable $e) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success'   => true,
                'message'   => 'Procedure request submitted (' . $procedureRequest->request_number . ').',
                'procedure' => $procedureRequest->only(['id', 'request_number', 'status', 'priority']),
            ]);
        }

        return redirect()
            ->route('admin.consultations.show', $visit)
            ->withFragment('procedures-section')
            ->with('success', 'Procedure request submitted (' . $procedureRequest->request_number . ').');
    }

    /**
     * Return complaint description suggestions from existing complaints.
     */
    public function suggestComplaints(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $suggestions = \App\Models\Complaint::where('description', 'like', '%' . $q . '%')
            ->distinct()
            ->orderByRaw('COUNT(*) DESC')
            ->groupBy('description')
            ->limit(10)
            ->pluck('description');

        return response()->json($suggestions);
    }

    /**
     * Return diagnosis description suggestions from existing diagnoses.
     */
    public function suggestDiagnoses(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $suggestions = \App\Models\Diagnosis::where('description', 'like', '%' . $q . '%')
            ->distinct()
            ->orderByRaw('COUNT(*) DESC')
            ->groupBy('description')
            ->limit(10)
            ->pluck('description');

        return response()->json($suggestions);
    }

    /*
    |--------------------------------------------------------------------------
    | Lab Request from Consultation
    |--------------------------------------------------------------------------
    */

    public function storeLabRequest(Request $request, Visit $visit)
    {
        $request->validate([
            'target_department_id' => ['required', 'exists:departments,id'],
            'items'                => ['required', 'array', 'min:1'],
            // items can be test IDs (int) or free-text names (string)
            'urgency'              => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_info'        => ['nullable', 'string', 'max:2000'],
        ]);

        $labRequest = $this->labService->createRequest(
            $visit,
            $request->input('items', []),
            $request->only('target_department_id', 'urgency', 'clinical_info')
        );

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'labRequest' => $labRequest]);
        }

        return back()->with('success', "Investigation request {$labRequest->request_number} sent to {$labRequest->targetDepartment?->name}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Visit Transition from Consultation
    |--------------------------------------------------------------------------
    */

    public function transitionVisit(Request $request, Visit $visit)
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

        return redirect()
            ->route('admin.consultations.index')
            ->with('success', "Visit moved to {$newStatus->label()}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Referral to Another Consultation Department
    |--------------------------------------------------------------------------
    */

    public function refer(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->visitService->referPatient($visit, (int) $request->department_id, $request->notes);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $dept = \App\Models\Department::find($request->department_id);

        return redirect()
            ->route('admin.consultations.index')
            ->with('success', "Patient referred to {$dept?->name}.");
    }

    /*
    |--------------------------------------------------------------------------
    | Send to Investigation
    |--------------------------------------------------------------------------
    */

    public function sendToInvestigation(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->visitService->sendToInvestigation($visit, (int) $request->department_id, $request->notes);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $dept = \App\Models\Department::find($request->department_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Patient sent to {$dept?->name} for investigation.",
                'department' => [
                    'id' => $dept?->id,
                    'name' => $dept?->name,
                ],
            ]);
        }

        return redirect()
            ->route('admin.consultations.index')
            ->with('success', "Patient sent to {$dept?->name} for investigation.");
    }
}
