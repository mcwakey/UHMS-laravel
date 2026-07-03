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
use App\Services\Consultation\PrescriptionSafetyException;
use App\Services\ActivityLogService;
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

trait HandlesConsultationPrescriptions
{

    public function storePrescription(StoreConsultationPrescriptionRequest $request, Visit $visit)
    {
        try {
            $context = $this->consultationMutationContext($request, $visit, 'prescription.create', 'prescriptions.create');
            $prescription = $this->prescriptionWorkflow->create($request, $visit, $context, $request->validated());
        } catch (PrescriptionSafetyException $e) {
            return $this->prescriptionSafetyFailureResponse($request, $e, $visit, $context ?? null);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'prescription' => $prescription->load(['items', 'creator', 'doctor', 'updater', 'sourcePattern'])]);
        }

        return redirect()
            ->route('admin.consultations.show', $visit)
            ->withFragment('prescriptions-section')
            ->with('success', __('messages.consultations.prescription_created', ['number' => $prescription->prescription_number]));
    }

    protected function prescriptionSafetyFailureResponse(
        Request $request,
        PrescriptionSafetyException $e,
        ?Visit $visit = null,
        ?ConsultationActionContext $context = null,
    ) {
        $this->logPrescriptionSafetyFailure($e, $visit, $context);

        if ($this->shouldReturnJson($request) || $request->expectsJson()) {
            return response()->json(array_merge([
                'success' => false,
                'message' => $e->getMessage(),
                'event' => $e->event,
                'requires_override' => $e->requiresOverride,
            ], $e->result->toArray()), $e->status);
        }

        return back()
            ->withInput()
            ->with('error', $e->getMessage())
            ->with('prescription_safety', $e->result->toArray());
    }

    protected function logPrescriptionSafetyFailure(
        PrescriptionSafetyException $e,
        ?Visit $visit,
        ?ConsultationActionContext $context,
    ): void
    {
        if (! $visit || ! $context) {
            return;
        }

        $event = $e->requiresOverride
            ? 'PRESCRIPTION_SAFETY_WARNING_TRIGGERED'
            : 'PRESCRIPTION_SAFETY_BLOCKED';

        app(ActivityLogService::class)->log(\App\Enums\LogModule::CONSULTATION, $event, [
            'severity' => \App\Enums\LogSeverity::WARNING,
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'consultation_route_id' => $context->route->id,
            'medical_record_id' => $context->medicalRecord->id,
            'causer' => $context->user,
            'warnings' => $e->result->warnings(),
            'blocking_errors' => $e->result->blockingErrors(),
        ], $context->route, __('consultation.safety.prescription_warning'));
    }

    public function updatePrescription(Request $request, Prescription $prescription)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $prescription), 403);

        if (! in_array($prescription->status->value, ['pending', 'active'], true)) {
            $message = 'Cannot edit a prescription that has already been dispensed or cancelled.';
            if ($this->shouldReturnJson($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $old = $prescription->getOriginal();
        $prescription->update(array_merge($data, ['updated_by' => Auth::id()]));
        app(MedicalRecordEntryLogService::class)->updated($prescription, $old, Auth::user());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'prescription' => $prescription->fresh(['items', 'creator', 'doctor', 'updater', 'sourcePattern'])]);
        }

        return back()->withFragment('prescriptions-section')->with('success', __('messages.consultations.prescription_updated'));
    }

    public function destroyPrescription(Prescription $prescription)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $prescription), 403);

        // Only allow deletion of pending/active prescriptions
        $allowedStatuses = ['pending', 'active'];
        if (! in_array($prescription->status->value, $allowedStatuses)) {
            if ($this->shouldReturnJson(request())) {
                return response()->json(['success' => false, 'message' => __('messages.consultations.prescription_cannot_delete')], 422);
            }

            return back()->with('error', __('messages.consultations.prescription_cannot_delete'));
        }

        $prescription->items()->delete();
        $prescription->delete();

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.prescription_deleted'));
    }
}
