<?php

namespace App\Http\Controllers\Doctor\Consultations;

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
use App\Services\Consultation\ConsultationClinicalEntryWorkflowService;
use App\Services\Consultation\ConsultationCompletionException;
use App\Services\Consultation\ConsultationCompletionReadinessService;
use App\Services\Consultation\ConsultationIdempotencyService;
use App\Services\Consultation\ConsultationOrderWorkflowService;
use App\Services\Consultation\ConsultationPlanningWorkflowService;
use App\Services\Consultation\ConsultationPrescriptionWorkflowService;
use App\Services\Consultation\ConsultationSessionWorkflowService;
use App\Services\Consultation\ConsultationWorkspacePayloadService;
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

abstract class ConsultationWorkflowController extends Controller
{

    public function __construct(
        protected ConsultationService $consultationService,
        protected ConsultationRouteService $consultationRouteService,
        protected ConsultationSessionService $consultationSessionService,
        protected PrescriptionService $prescriptionService,
        protected VisitService $visitService,
        protected MedicalPatternService $patternService,
        protected LabService $labService,
        protected ClinicalService $clinicalService,
        protected HistoryOfPresentingComplaintService $hopcService,
        protected PhysicalExaminationService $examinationService,
        protected MedicalRecordEntryPermissionService $entryPermissions,
        protected ConsultationSummaryService $summaryService,
        protected ServicePriceResolver $priceResolver,
        protected ConsultationActionGuard $actionGuard,
        protected ConsultationIdempotencyService $idempotency,
        protected ConsultationWorkspacePayloadService $workspacePayloads,
        protected ConsultationOrderWorkflowService $orderWorkflow,
        protected ConsultationPrescriptionWorkflowService $prescriptionWorkflow,
        protected ConsultationPlanningWorkflowService $planningWorkflow,
        protected ConsultationSessionWorkflowService $sessionWorkflow,
        protected ConsultationClinicalEntryWorkflowService $clinicalEntryWorkflow,
        protected ConsultationCompletionReadinessService $completionReadiness,
    ) {}

    protected function shouldReturnJson(Request $request): bool
    {
        return ($request->ajax() || $request->expectsJson()) && ! $request->headers->has('X-Inertia');
    }

    protected function entryPayload($entry, array $relations = [])
    {
        $defaultRelations = [
            'creator',
            'doctor',
            'updater',
            'sourcePattern',
        ];

        if ($entry instanceof Complaint) {
            $defaultRelations[] = 'complaintCatalogue';
        }

        return $entry->fresh(array_values(array_unique(array_merge($defaultRelations, $relations))));
    }

    protected function abortIfRouteMismatch(Visit $visit, VisitConsultationRoute $route): void
    {
        if ((int) $route->visit_id !== (int) $visit->id) {
            abort(404);
        }
    }

    protected function userHasAnyRole(User $user, array $roles): bool
    {
        return is_callable([$user, 'hasAnyRole']) && (bool) call_user_func([$user, 'hasAnyRole'], $roles);
    }

    protected function userCan(User $user, string $ability): bool
    {
        return Gate::forUser($user)->allows($ability);
    }

    protected function consultationMutationContext(Request $request, Visit $visit, string $action, ?string $ability = null): ConsultationActionContext
    {
        return $this->actionGuard->editable(
            $visit,
            $request->integer('consultation_route_id') ?: null,
            Auth::user(),
            $action,
            $ability,
        );
    }

    protected function consultationActionFailureResponse(Request $request, ConsultationActionException $e)
    {
        if ($this->shouldReturnJson($request) || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'event' => $e->event,
            ], $e->status);
        }

        return back()->withInput()->with('error', $e->getMessage());
    }

    protected function consultationCompletionFailureResponse(Request $request, ConsultationCompletionException $e)
    {
        if ($this->shouldReturnJson($request) || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'event' => $e->event,
                'readiness' => $e->result->toArray(),
            ], $e->status);
        }

        return back()
            ->withInput()
            ->with('error', $e->getMessage())
            ->with('completion_readiness', $e->result->toArray());
    }

    /**
     * Doctor explicitly starts the consultation (now a no-op since triage moves directly to CONSULTING).
     */
}
