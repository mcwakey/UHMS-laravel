<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Complaint;
use App\Models\Diagnosis;
use App\Models\Investigation;
use App\Models\Treatment;
use App\Models\Visit;
use App\Services\ConsultationService;
use App\Services\LabService;
use App\Services\MedicalPatternService;
use App\Services\PrescriptionService;
use App\Services\VisitService;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function __construct(
        protected ConsultationService $consultationService,
        protected PrescriptionService $prescriptionService,
        protected VisitService $visitService,
        protected MedicalPatternService $patternService,
        protected LabService $labService,
    ) {}

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

        $query = Visit::with(['patient', 'assignedDoctor', 'medicalRecord'])
            ->whereIn('status', [
                VisitStatus::CONSULTING->value,
                VisitStatus::TRIAGE->value,
                VisitStatus::LAB->value,
            ]);

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
            $query->where('assigned_doctor_id', auth()->id());
        }

        // No department column on visits — filter by status instead (done above)

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
            ->forDoctor(auth()->id())
            ->with('items')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // Lab data
        $labRequests = $this->labService->getVisitLabRequests($visit);
        $labCategories = $this->labService->getActiveCategories();

        // Doctors for task assignment
        $doctors = \App\Models\User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        return view('consultations.show', [
            'visit' => $data['visit'],
            'record' => $data['record'],
            'vitals' => $data['vitals'],
            'history' => $data['history'],
            'patterns' => $patterns,
            'labRequests' => $labRequests,
            'labCategories' => $labCategories,
            'doctors' => $doctors,
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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'complaint' => $complaint]);
        }

        return back()->with('success', 'Complaint added.');
    }

    public function destroyComplaint(Complaint $complaint)
    {
        $this->consultationService->deleteComplaint($complaint);

        if (request()->ajax()) {
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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'diagnosis' => $diagnosis]);
        }

        return back()->with('success', 'Diagnosis added.');
    }

    public function destroyDiagnosis(Diagnosis $diagnosis)
    {
        $this->consultationService->deleteDiagnosis($diagnosis);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Diagnosis removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Investigation CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeInvestigation(Request $request, Visit $visit)
    {
        $request->validate([
            'investigation_type' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->consultationService->getOrCreateRecord($visit);
        $investigation = $this->consultationService->addInvestigation($record, $request->only('investigation_type', 'description', 'urgency', 'notes'));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'investigation' => $investigation]);
        }

        return back()->with('success', 'Investigation added.');
    }

    public function destroyInvestigation(Investigation $investigation)
    {
        $this->consultationService->deleteInvestigation($investigation);

        if (request()->ajax()) {
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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'treatment' => $treatment]);
        }

        return back()->with('success', 'Treatment added.');
    }

    public function destroyTreatment(Treatment $treatment)
    {
        $this->consultationService->deleteTreatment($treatment);

        if (request()->ajax()) {
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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'prescription' => $prescription->load('items')]);
        }

        return back()->with('success', "Prescription {$prescription->prescription_number} created.");
    }

    /*
    |--------------------------------------------------------------------------
    | Lab Request from Consultation
    |--------------------------------------------------------------------------
    */

    public function storeLabRequest(Request $request, Visit $visit)
    {
        $request->validate([
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['exists:lab_tests,id'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
        ]);

        $labRequest = $this->labService->createRequest($visit, $request->test_ids, $request->only('urgency', 'clinical_info'));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'labRequest' => $labRequest]);
        }

        return back()->with('success', "Lab request {$labRequest->request_number} created.");
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
}
