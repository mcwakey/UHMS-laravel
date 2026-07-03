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
use App\Services\Consultation\ConsultationCompletionException;
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

trait HandlesConsultationPlanning
{

    public function storeFollowUpAppointment(
        StoreConsultationFollowUpRequest $request,
        Visit $visit,
        VisitConsultationRoute $route,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validated();

        try {
            $this->planningWorkflow->createFollowUp($visit, $route, $data, Auth::user());
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.followup_saved'));
    }

    public function updateFollowUpAppointment(
        StoreConsultationFollowUpRequest $request,
        Visit $visit,
        VisitConsultationRoute $route,
        Appointment $appointment,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validated();

        try {
            $this->planningWorkflow->updateFollowUp($appointment, $visit, $route, $data, Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.followup_updated'));
    }

    public function cancelFollowUpAppointment(
        Request $request,
        Visit $visit,
        VisitConsultationRoute $route,
        Appointment $appointment,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->planningWorkflow->cancelFollowUp($appointment, $visit, $route, $data['reason'], Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.followup_cancelled'));
    }

    public function openNextPatient(
        Request $request,
        Visit $visit,
        VisitConsultationRoute $route,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        try {
            $nextRoute = $this->planningWorkflow->openNext($route, Auth::user(), false);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$nextRoute->visit, $nextRoute])
            ->with('success', __('messages.consultations.next_patient_opened'));
    }

    public function completeAndOpenNextPatient(
        Request $request,
        Visit $visit,
        VisitConsultationRoute $route,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        try {
            $nextRoute = $this->planningWorkflow->openNext($route, Auth::user(), true);
        } catch (ConsultationCompletionException $e) {
            return $this->consultationCompletionFailureResponse($request, $e);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$nextRoute->visit, $nextRoute])
            ->with('success', __('messages.consultations.completed_next_opened'));
    }

    public function refer(StoreConsultationReferralRequest $request, Visit $visit)
    {
        $data = $request->validated();

        try {
            $this->consultationMutationContext($request, $visit, 'consultation.refer', 'consultations.create');
            $route = $this->planningWorkflow->refer($visit, $data, Auth::user());
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $dept = Department::find($data['department_id']);

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', (bool) ($data['activate_now'] ?? false)
                ? __('messages.consultations.referred_activated', ['department' => $dept?->name])
                : __('messages.consultations.referred_queued', ['department' => $dept?->name]));
    }
}
