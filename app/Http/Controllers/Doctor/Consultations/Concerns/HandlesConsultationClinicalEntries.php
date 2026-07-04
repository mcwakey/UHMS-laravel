<?php

namespace App\Http\Controllers\Doctor\Consultations\Concerns;

use App\Enums\AppointmentStatus;
use App\Enums\DepartmentType;
use App\Enums\Priority;
use App\Enums\ProcedureStatus;
use App\Enums\ResultType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Consultations\CancelConsultationRouteRequest;
use App\Http\Requests\Consultations\CompleteConsultationRouteRequest;
use App\Http\Requests\Consultations\StoreConsultationDiagnosisRequest;
use App\Http\Requests\Consultations\StoreConsultationFollowUpRequest;
use App\Http\Requests\Consultations\StoreConsultationLabRequest;
use App\Http\Requests\Consultations\StoreConsultationPrescriptionRequest;
use App\Http\Requests\Consultations\StoreConsultationProcedureRequest;
use App\Http\Requests\Consultations\StoreConsultationReferralRequest;
use App\Http\Requests\Consultations\TransitionConsultationRouteRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Appointment;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Diagnosis;
use App\Models\Drug;
use App\Models\HistoryOfPresentingComplaint;
use App\Models\Investigation;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabTest;
use App\Models\MedicalPattern;
use App\Models\PatientProcedure;
use App\Models\PhysicalExamination;
use App\Models\Prescription;
use App\Models\Procedure;
use App\Models\ProcedureRequest;
use App\Models\QueueEntry;
use App\Models\ServiceCatalog;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ClinicalService;
use App\Services\ComplaintSearchService;
use App\Services\ConsultationFollowUpService;
use App\Services\ConsultationNextPatientService;
use App\Services\ConsultationRouteService;
use App\Services\ConsultationService;
use App\Services\ConsultationSessionService;
use App\Services\ConsultationSummaryService;
use App\Services\Consultation\ConsultationActionContext;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\ConsultationActionGuard;
use App\Services\Consultation\ConsultationIdempotencyService;
use App\Services\HistoryOfPresentingComplaintService;
use App\Services\LabService;
use App\Services\MedicalPatternService;
use App\Services\MedicalRecordEntryLogService;
use App\Services\MedicalRecordEntryPermissionService;
use App\Services\PhysicalExaminationService;
use App\Services\PrescriptionService;
use App\Services\ProcedureRequestService;
use App\Services\ServicePriceResolver;
use App\Services\VisitService;
use App\Services\VisitWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

trait HandlesConsultationClinicalEntries
{

    public function storeComplaint(Request $request, Visit $visit)
    {
        $request->validate([
            'complaint_catalogue_id' => ['nullable', Rule::exists('complaint_catalogues', 'id')->where('is_active', true)],
            'description' => ['required_without:complaint_catalogue_id', 'nullable', 'string', 'max:2000'],
            'duration' => ['nullable', 'string', 'max:191'],
            'duration_unit' => ['nullable', 'in:minutes,hours,days,weeks,months,years'],
            'severity' => ['nullable', 'in:mild,moderate,severe,critical'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $context = $this->consultationMutationContext($request, $visit, 'complaint.create', 'consultations.create');
            $complaint = $this->clinicalEntryWorkflow->createComplaint(
                $request,
                $visit,
                $context,
                $request->only('complaint_catalogue_id', 'description', 'duration', 'duration_unit', 'severity', 'notes'),
            );
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'complaint' => $this->entryPayload($complaint)]);
        }

        return back()->with('success', __('messages.consultations.complaint_added'));
    }

    public function updateComplaint(Request $request, Complaint $complaint)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $complaint), 403);

        $data = $request->validate([
            'complaint_catalogue_id' => ['nullable', Rule::exists('complaint_catalogues', 'id')->where('is_active', true)],
            'description' => ['required_without:complaint_catalogue_id', 'nullable', 'string', 'max:2000'],
            'duration' => ['nullable', 'string', 'max:191'],
            'duration_unit' => ['nullable', 'in:minutes,hours,days,weeks,months,years'],
            'severity' => ['nullable', 'in:mild,moderate,severe,critical'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $complaint = $this->clinicalEntryWorkflow->updateComplaint($complaint, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'complaint' => $this->entryPayload($complaint)]);
        }

        return back()->withFragment('complaints-section')->with('success', __('messages.consultations.complaint_updated'));
    }

    public function destroyComplaint(Complaint $complaint)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $complaint), 403);

        $this->clinicalEntryWorkflow->deleteComplaint($complaint);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.complaint_removed'));
    }

    public function storeHistoryOfPresentingComplaint(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'complaint_id' => ['nullable', 'exists:complaints,id'],
            'content' => ['required_without_all:complaint_id,onset,duration,location,character,radiation,associated_symptoms,aggravating_factors,relieving_factors,severity,timing,notes', 'nullable', 'string', 'max:8000'],
            'onset' => ['nullable', 'string', 'max:191'],
            'duration' => ['nullable', 'string', 'max:191'],
            'location' => ['nullable', 'string', 'max:191'],
            'character' => ['nullable', 'string', 'max:191'],
            'radiation' => ['nullable', 'string', 'max:191'],
            'associated_symptoms' => ['nullable', 'string', 'max:2000'],
            'aggravating_factors' => ['nullable', 'string', 'max:2000'],
            'relieving_factors' => ['nullable', 'string', 'max:2000'],
            'severity' => ['nullable', 'string', 'max:191'],
            'timing' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $context = $this->consultationMutationContext($request, $visit, 'hopc.create', 'consultations.create');
            $data = app(\App\Services\Consultation\HopcComplaintHydrationService::class)->hydrate($data);
            $entry = $this->clinicalEntryWorkflow->createHistoryOfPresentingComplaint($request, $visit, $context, $data, Auth::user());
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'hopc' => $this->entryPayload($entry, ['complaint'])]);
        }

        return back()->withFragment('hopc-section')->with('success', __('messages.consultations.hopc_added'));
    }

    public function updateHistoryOfPresentingComplaint(Request $request, HistoryOfPresentingComplaint $hopc)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $hopc), 403);

        $data = $request->validate([
            'complaint_id' => ['nullable', 'exists:complaints,id'],
            'content' => ['required_without_all:complaint_id,onset,duration,location,character,radiation,associated_symptoms,aggravating_factors,relieving_factors,severity,timing,notes', 'nullable', 'string', 'max:8000'],
            'onset' => ['nullable', 'string', 'max:191'],
            'duration' => ['nullable', 'string', 'max:191'],
            'location' => ['nullable', 'string', 'max:191'],
            'character' => ['nullable', 'string', 'max:191'],
            'radiation' => ['nullable', 'string', 'max:191'],
            'associated_symptoms' => ['nullable', 'string', 'max:2000'],
            'aggravating_factors' => ['nullable', 'string', 'max:2000'],
            'relieving_factors' => ['nullable', 'string', 'max:2000'],
            'severity' => ['nullable', 'string', 'max:191'],
            'timing' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = app(\App\Services\Consultation\HopcComplaintHydrationService::class)->hydrate($data);
        $hopc = $this->clinicalEntryWorkflow->updateHistoryOfPresentingComplaint($hopc, $data, Auth::user());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'hopc' => $this->entryPayload($hopc, ['complaint'])]);
        }

        return back()->withFragment('hopc-section')->with('success', __('messages.consultations.hopc_updated'));
    }

    public function destroyHistoryOfPresentingComplaint(HistoryOfPresentingComplaint $hopc)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $hopc), 403);

        $this->clinicalEntryWorkflow->deleteHistoryOfPresentingComplaint($hopc, Auth::user());

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->withFragment('hopc-section')->with('success', __('messages.consultations.hopc_removed'));
    }

    public function storeExamination(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'findings' => ['required_without_all:general_examination,systemic_examination,cardiovascular,respiratory,gastrointestinal,central_nervous_system,musculoskeletal,specialty_examination,local_examination,notes', 'nullable', 'string', 'max:8000'],
            'general_examination' => ['nullable', 'string', 'max:2000'],
            'systemic_examination' => ['nullable', 'string', 'max:2000'],
            'cardiovascular' => ['nullable', 'string', 'max:2000'],
            'respiratory' => ['nullable', 'string', 'max:2000'],
            'gastrointestinal' => ['nullable', 'string', 'max:2000'],
            'central_nervous_system' => ['nullable', 'string', 'max:2000'],
            'musculoskeletal' => ['nullable', 'string', 'max:2000'],
            'specialty_examination' => ['nullable', 'string', 'max:2000'],
            'local_examination' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $context = $this->consultationMutationContext($request, $visit, 'examination.create', 'consultations.create');
            $entry = $this->clinicalEntryWorkflow->createExamination($request, $visit, $context, $data, Auth::user());
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'examination' => $this->entryPayload($entry)]);
        }

        return back()->withFragment('examination-section')->with('success', __('messages.consultations.examination_added'));
    }

    public function updateExamination(Request $request, PhysicalExamination $examination)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $examination), 403);

        $data = $request->validate([
            'findings' => ['required_without_all:general_examination,systemic_examination,cardiovascular,respiratory,gastrointestinal,central_nervous_system,musculoskeletal,specialty_examination,local_examination,notes', 'nullable', 'string', 'max:8000'],
            'general_examination' => ['nullable', 'string', 'max:2000'],
            'systemic_examination' => ['nullable', 'string', 'max:2000'],
            'cardiovascular' => ['nullable', 'string', 'max:2000'],
            'respiratory' => ['nullable', 'string', 'max:2000'],
            'gastrointestinal' => ['nullable', 'string', 'max:2000'],
            'central_nervous_system' => ['nullable', 'string', 'max:2000'],
            'musculoskeletal' => ['nullable', 'string', 'max:2000'],
            'specialty_examination' => ['nullable', 'string', 'max:2000'],
            'local_examination' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $examination = $this->clinicalEntryWorkflow->updateExamination($examination, $data, Auth::user());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'examination' => $this->entryPayload($examination)]);
        }

        return back()->withFragment('examination-section')->with('success', __('messages.consultations.examination_updated'));
    }

    public function destroyExamination(PhysicalExamination $examination)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $examination), 403);

        $this->clinicalEntryWorkflow->deleteExamination($examination, Auth::user());

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->withFragment('examination-section')->with('success', __('messages.consultations.examination_removed'));
    }

    public function storeDiagnosis(StoreConsultationDiagnosisRequest $request, Visit $visit)
    {
        $data = $request->validated();

        try {
            $context = $this->consultationMutationContext($request, $visit, 'diagnosis.create', 'consultations.create');
            $diagnosis = $this->clinicalEntryWorkflow->createDiagnosis($request, $visit, $context, $data);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'diagnosis' => $this->entryPayload($diagnosis, ['icdCodeEntry'])]);
        }

        return back()->with('success', __('messages.consultations.diagnosis_added'));
    }

    public function destroyDiagnosis(Diagnosis $diagnosis)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $diagnosis), 403);

        $this->clinicalEntryWorkflow->deleteDiagnosis($diagnosis);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.diagnosis_removed'));
    }

    public function updateDiagnosis(Request $request, Diagnosis $diagnosis)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $diagnosis), 403);

        $data = $request->validate([
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'icd_code' => ['nullable', 'string', 'max:20'],
            'icd_code_id' => ['nullable', 'exists:icd_codes,id'],
            'type' => ['sometimes', 'required', 'in:provisional,final'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $diagnosis = $this->clinicalEntryWorkflow->updateDiagnosis($diagnosis, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'type' => $diagnosis->fresh()->type,
                'diagnosis' => $this->entryPayload($diagnosis, ['icdCodeEntry']),
            ]);
        }

        return back()->with('success', __('messages.consultations.diagnosis_updated'));
    }

    public function setPrimaryDiagnosis(Diagnosis $diagnosis)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $diagnosis), 403);

        $this->clinicalEntryWorkflow->setPrimaryDiagnosis($diagnosis);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.primary_diagnosis_set'));
    }

    public function storeTreatment(Request $request, Visit $visit)
    {
        $request->validate([
            'type' => ['required', 'in:medication,procedure,referral,advice'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $context = $this->consultationMutationContext($request, $visit, 'treatment.create', 'consultations.create');
            $treatment = $this->clinicalEntryWorkflow->createTreatment(
                $request,
                $visit,
                $context,
                $request->only('type', 'description'),
            );
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'treatment' => $this->entryPayload($treatment)]);
        }

        return back()->with('success', __('messages.consultations.treatment_added'));
    }

    public function updateTreatment(Request $request, Treatment $treatment)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $treatment), 403);

        $data = $request->validate([
            'type' => ['required', 'in:medication,procedure,referral,advice'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $treatment = $this->clinicalEntryWorkflow->updateTreatment($treatment, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'treatment' => $this->entryPayload($treatment)]);
        }

        return back()->withFragment('treatments-section')->with('success', __('messages.consultations.treatment_updated'));
    }

    public function destroyTreatment(Treatment $treatment)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $treatment), 403);

        $this->clinicalEntryWorkflow->deleteTreatment($treatment);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.treatment_removed'));
    }

    public function suggestComplaints(Request $request, ComplaintSearchService $complaintSearch)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json($complaintSearch->autocompletePayload($complaintSearch->search($q, 10)));
    }

    public function suggestDiagnoses(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $suggestions = Diagnosis::where('description', 'like', '%'.$q.'%')
            ->distinct()
            ->orderByRaw('COUNT(*) DESC')
            ->groupBy('description')
            ->limit(10)
            ->pluck('description');

        return response()->json($suggestions);
    }
}
