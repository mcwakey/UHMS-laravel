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

trait HandlesConsultationSessions
{

    public function startConsultation(Request $request, Visit $visit, VisitWorkflowService $workflow)
    {
        try {
            $workflow->startConsultation(
                $visit,
                Auth::user(),
                $request->integer('consultation_route_id') ?: null,
            );
        } catch (\Throwable $e) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($this->shouldReturnJson($request)) {
            $activeRoute = $visit->fresh()->activeConsultationRoute()->first();

            return response()->json([
                'success' => 'Consultation started.',
                'redirect' => $activeRoute
                    ? route('admin.consultations.routes.show', [$visit, $activeRoute])
                    : route('admin.consultations.show', $visit),
            ]);
        }

        $activeRoute = $visit->fresh()->activeConsultationRoute()->first();

        return redirect()
            ->to($activeRoute
                ? route('admin.consultations.routes.show', [$visit, $activeRoute])
                : route('admin.consultations.show', $visit))
            ->with('success', __('messages.consultations.started'));
    }

    public function storeRoute(Request $request, Visit $visit)
    {
        if (app(\App\Services\Consultation\ConsultationSessionEligibilityService::class)->isVisitClinicallyLocked($visit)) {
            $message = __('consultations.lock_reasons.visit_closed_after_visit_day');

            if ($this->shouldReturnJson($request)) {
                return response()->json(['error' => $message], 422);
            }

            return back()->withInput()->with('error', $message);
        }

        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'service_id' => ['nullable', 'exists:service_catalog,id'],
            'service_ids' => ['required_without:service_id', 'array', 'min:1'],
            'service_ids.*' => ['required', 'exists:service_catalog,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'activate_now' => ['nullable', 'boolean'],
        ]);

        try {
            $serviceIds = collect($data['service_ids'] ?? [])
                ->when(! empty($data['service_id']), fn ($ids) => $ids->push($data['service_id']))
                ->filter()
                ->unique()
                ->values();

            $route = $this->consultationRouteService->sendToAnotherConsultation(
                visit: $visit,
                department: Department::findOrFail($data['department_id']),
                service: $serviceIds->isNotEmpty()
                    ? ServiceCatalog::whereIn('id', $serviceIds)->get()
                    : null,
                doctor: ! empty($data['doctor_id']) ? User::findOrFail($data['doctor_id']) : null,
                routedBy: Auth::user(),
                notes: $data['notes'] ?? null,
                activateNow: (bool) ($data['activate_now'] ?? false),
            );
        } catch (\Throwable $e) {
            if ($this->shouldReturnJson($request)) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = ($data['activate_now'] ?? false)
            ? __('messages.consultations.route_activated')
            : __('messages.consultations.route_queued');

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => $message,
                'route_id' => $route->id,
                'redirect' => route('admin.consultations.routes.show', [$visit, $route]),
            ]);
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', $message);
    }

    public function activateRoute(Request $request, Visit $visit, VisitConsultationRoute $route)
    {
        $this->abortIfRouteMismatch($visit, $route);

        try {
            if ($this->visitRequiresReopenForConsultationActivation($visit)) {
                $reason = $request->input('reason', __('consultations.reopen.visit_details_reason'));
                $this->sessionWorkflow->reopenVisitForRouteActivation($visit, $route, Auth::user(), $reason);
                $visit = $visit->fresh(['consultationRoutes']);
                $route = $route->fresh();
            }

            if ($request->boolean('route_only')) {
                $route = $this->consultationRouteService->activateRouteOnly($route, Auth::user());

                return redirect()
                    ->route('admin.visits.show', $visit)
                    ->with('success', __('messages.consultations.route_activated_queued'));
            }

            $route = $this->consultationRouteService->activateRoute($route, Auth::user());
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->boolean('return_to_visit')) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('success', __('messages.consultations.route_activated'));
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.route_activated'));
    }

    public function completeRoute(CompleteConsultationRouteRequest $request, Visit $visit, VisitConsultationRoute $route)
    {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validated();

        try {
            $route = $this->sessionWorkflow->completeRoute($visit, $route, Auth::user(), $data['notes'] ?? null);
        } catch (ConsultationCompletionException $e) {
            return $this->consultationCompletionFailureResponse($request, $e);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('messages.consultations.route_completed'),
                'route_id' => $route->id,
            ]);
        }

        if ($request->boolean('return_to_visit')) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('success', __('messages.consultations.route_completed'));
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.route_completed'));
    }

    public function cancelRoute(CancelConsultationRouteRequest $request, Visit $visit, VisitConsultationRoute $route)
    {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validated();

        try {
            $route = $this->sessionWorkflow->cancelRoute($visit, $route, Auth::user(), $data['reason'] ?? null);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.route_cancelled'));
    }

    public function reopenRoute(Request $request, Visit $visit, VisitConsultationRoute $route)
    {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'target_status' => ['nullable', 'string', Rule::in([VisitConsultationRoute::STATUS_ACTIVE])],
        ]);

        try {
            $route = $this->sessionWorkflow->reopenRoute($visit, $route, Auth::user(), $data['reason']);
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('consultations.reopen.success'),
                'route_id' => $route->id,
                'redirect' => route('admin.consultations.routes.show', [$visit, $route]),
            ]);
        }

        if ($request->boolean('return_to_visit')) {
            return redirect()
                ->route('admin.visits.show', $visit)
                ->with('success', __('consultations.reopen.success'));
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('consultations.reopen.success'));
    }

    public function transitionVisit(TransitionConsultationRouteRequest $request, Visit $visit)
    {
        $data = $request->validated();

        try {
            $this->consultationMutationContext($request, $visit, 'visit.transition', 'visits.transition');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        try {
            $newStatus = $this->sessionWorkflow->transitionVisit($visit, $data['status'], $data['notes'] ?? null, Auth::user());
        } catch (ConsultationCompletionException $e) {
            return $this->consultationCompletionFailureResponse($request, $e);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.index')
            ->with('success', __('messages.consultations.visit_transitioned', ['status' => $newStatus->label()]));
    }

    private function visitRequiresReopenForConsultationActivation(Visit $visit): bool
    {
        return in_array($visit->status, [
            VisitStatus::COMPLETED,
            VisitStatus::DISCHARGED,
        ], true);
    }

}
