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

trait HandlesConsultationOrders
{

    public function destroyInvestigationItem(Request $request, LabRequestItem $item)
    {
        if (! $item->isDeletable()) {
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

        return back()->with('success', __('messages.consultations.investigation_removed'));
    }

    public function getDepartmentServices(Request $request, Department $department)
    {
        $visit = $request->integer('visit_id')
            ? Visit::with('visitInsurance.insuranceProvider')->find($request->integer('visit_id'))
            : null;

        return response()->json($this->orderWorkflow->departmentServices($department, $visit));
    }

    public function getDepartmentInvestigationInfo(Department $department)
    {
        return response()->json($this->orderWorkflow->departmentInvestigationInfo($department));
    }

    public function storeInvestigation(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'service_ids' => ['required_without:investigation_type', 'array', 'min:1'],
            'service_ids.*' => ['exists:service_catalog,id'],
            'investigation_type' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $context = $this->consultationMutationContext($request, $visit, 'investigation.create', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        [$created, $labRequest] = $this->orderWorkflow->createInvestigation($visit, $context, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'investigations' => collect($created)->map(fn ($entry) => $this->entryPayload($entry))->values(),
                'count' => count($created),
                'lab_request' => $labRequest?->fresh(['targetDepartment', 'requestedBy', 'items.labTest', 'items.service', 'items.result']),
            ]);
        }

        $msg = $labRequest
            ? __('messages.consultations.investigations_added_with_request', ['count' => count($created), 'number' => $labRequest->request_number])
            : __('messages.consultations.investigations_added', ['count' => count($created)]);

        return back()->with('success', $msg);
    }

    public function updateInvestigation(Request $request, Investigation $investigation)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $investigation), 403);

        $data = $request->validate([
            'investigation_type' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $investigation = $this->consultationService->updateInvestigation($investigation, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'investigation' => $this->entryPayload($investigation)]);
        }

        return back()->withFragment('investigations-section')->with('success', __('messages.consultations.investigation_updated'));
    }

    public function destroyInvestigation(Investigation $investigation)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $investigation), 403);

        $this->consultationService->deleteInvestigation($investigation);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.investigation_removed'));
    }

    public function storeProcedureRequest(StoreConsultationProcedureRequest $request, Visit $visit)
    {
        $data = $request->validated();

        try {
            $context = $this->consultationMutationContext($request, $visit, 'procedure.create', 'procedure.request');
            $procedureRequest = $this->orderWorkflow->createProcedureRequest($request, $visit, $context, $data, Auth::user());
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        } catch (\Throwable $e) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('messages.consultations.procedure_submitted', ['number' => $procedureRequest->request_number]),
                'procedure' => $procedureRequest->fresh(['service', 'department', 'requestingDoctor', 'schedule.theatreRoom']),
            ]);
        }

        return redirect()
            ->route('admin.consultations.show', $visit)
            ->withFragment('procedures-section')
            ->with('success', __('messages.consultations.procedure_submitted', ['number' => $procedureRequest->request_number]));
    }

    public function updateProcedureRequest(Request $request, ProcedureRequest $procedureRequest)
    {
        $user = Auth::user();
        abort_unless($user && (
            $this->userHasAnyRole($user, ['Super Admin', 'Admin'])
            || (int) $procedureRequest->requested_by === (int) $user->id
            || $this->userCan($user, 'consultation.entries.edit_any')
        ), 403);

        if ($procedureRequest->status !== ProcedureStatus::REQUESTED) {
            $message = 'Cannot edit this procedure request after it has entered the procedure workflow.';
            if ($this->shouldReturnJson($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $data = $request->validate([
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'indication' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
        ]);

        $old = $procedureRequest->getOriginal();
        $procedureRequest->update($data);
        app(MedicalRecordEntryLogService::class)->updated($procedureRequest, $old, $user);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'procedure' => $procedureRequest->fresh(['service', 'department', 'requestingDoctor', 'schedule.theatreRoom'])]);
        }

        return back()->withFragment('procedures-section')->with('success', __('messages.consultations.procedure_updated'));
    }

    public function storeLabRequest(StoreConsultationLabRequest $request, Visit $visit)
    {
        $data = $request->validated();

        try {
            $context = $this->consultationMutationContext($request, $visit, 'lab_request.create', 'lab.requests.create');
            $labRequest = $this->orderWorkflow->createLabRequest($request, $visit, $context, $data);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'labRequest' => $labRequest->fresh(['targetDepartment', 'requestedBy', 'items.labTest', 'items.service', 'items.result'])]);
        }

        return back()->with('success', __('messages.consultations.lab_request_sent', ['number' => $labRequest->request_number, 'department' => $labRequest->targetDepartment?->name]));
    }

    public function updateLabRequest(Request $request, LabRequest $labRequest)
    {
        $user = Auth::user();
        abort_unless($user && (
            $this->userHasAnyRole($user, ['Super Admin', 'Admin'])
            || (int) $labRequest->requested_by === (int) $user->id
            || $this->userCan($user, 'consultation.entries.edit_any')
        ), 403);

        $labRequest->loadMissing(['items.result']);
        $hasProcessedItem = $labRequest->items->contains(fn ($item) => $item->isAccepted() || $item->result);
        if ($labRequest->status !== 'pending' || $hasProcessedItem) {
            $message = 'Cannot edit this investigation request after it has been accepted, billed, or resulted.';
            if ($this->shouldReturnJson($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $data = $request->validate([
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
        ]);

        $old = $labRequest->getOriginal();
        $labRequest->update($data);
        app(MedicalRecordEntryLogService::class)->updated($labRequest, $old, $user);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'labRequest' => $labRequest->fresh(['targetDepartment', 'requestedBy', 'items.labTest', 'items.service', 'items.result'])]);
        }

        return back()->withFragment('investigations-section')->with('success', __('messages.consultations.lab_request_updated'));
    }

    public function sendToInvestigation(Request $request, Visit $visit)
    {
        $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->consultationMutationContext($request, $visit, 'consultation.send_to_investigation', 'consultations.create');
            $this->orderWorkflow->sendToInvestigation($visit, (int) $request->department_id, $request->notes);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $dept = Department::find($request->department_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.consultations.sent_to_investigation', ['department' => $dept?->name]),
                'department' => [
                    'id' => $dept?->id,
                    'name' => $dept?->name,
                ],
            ]);
        }

        return redirect()
            ->route('admin.consultations.index')
            ->with('success', __('messages.consultations.sent_to_investigation', ['department' => $dept?->name]));
    }
}
