<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\DepartmentType;
use App\Enums\AppointmentStatus;
use App\Enums\Priority;
use App\Enums\ProcedureStatus;
use App\Enums\ResultType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Http\Controllers\Controller;
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
use App\Services\ConsultationRouteService;
use App\Services\ConsultationFollowUpService;
use App\Services\ConsultationNextPatientService;
use App\Services\ConsultationService;
use App\Services\ConsultationSessionService;
use App\Services\ConsultationSummaryService;
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

class ConsultationController extends Controller
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
    ) {}

    private function shouldReturnJson(Request $request): bool
    {
        return $request->ajax() && ! $request->headers->has('X-Inertia');
    }

    private function entryPayload($entry, array $relations = [])
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

    private function abortIfRouteMismatch(Visit $visit, VisitConsultationRoute $route): void
    {
        if ((int) $route->visit_id !== (int) $visit->id) {
            abort(404);
        }
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
        return is_callable([$user, 'hasAnyRole']) && (bool) call_user_func([$user, 'hasAnyRole'], $roles);
    }

    private function userCan(User $user, string $ability): bool
    {
        return Gate::forUser($user)->allows($ability);
    }

    /**
     * Doctor explicitly starts the consultation (now a no-op since triage moves directly to CONSULTING).
     */
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

    /**
     * Delete a single LabRequestItem (doctor-side cancel) — disallowed once a result exists.
     */
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

    /**
     * List consultable visits (today's consulting visits).
     */
    public function index(Request $request)
    {
        $filters = $request->all();

        $dateRange = trim((string) ($filters['date_range'] ?? ''));
        if ($dateRange !== '') {
            $parts = preg_split('/\s+(?:to|-)\s+/', $dateRange);
            $filters['date_from'] = $parts[0] ?? null;
            $filters['date_to'] = $parts[1] ?? ($parts[0] ?? null);
        }

        // Default: today's active consultable routes across visit types.
        if (! $request->hasAny(['search', 'visit_type', 'date_from', 'date_to', 'date_range', 'my_patients'])) {
            $filters['date_from'] = $filters['date_from'] ?? today()->toDateString();
            $filters['date_to'] = $filters['date_to'] ?? today()->toDateString();
        }

        if (! empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_to'] = $filters['date_from'];
        }

        if (! empty($filters['date_to']) && empty($filters['date_from'])) {
            $filters['date_from'] = $filters['date_to'];
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $filters['date_range'] = $filters['date_from'].' to '.$filters['date_to'];
        }

        $routeTable = (new VisitConsultationRoute)->getTable();
        $queueNumberSubquery = QueueEntry::query()
            ->select('queue_number')
            ->whereColumn('visit_id', "{$routeTable}.visit_id")
            ->whereColumn('department_id', "{$routeTable}.department_id")
            ->where('status', 'waiting')
            ->orderBy('queue_number')
            ->limit(1);
        $queueCreatedSubquery = QueueEntry::query()
            ->select('created_at')
            ->whereColumn('visit_id', "{$routeTable}.visit_id")
            ->whereColumn('department_id', "{$routeTable}.department_id")
            ->where('status', 'waiting')
            ->orderBy('queue_number')
            ->limit(1);

        $query = VisitConsultationRoute::query()
            ->select("{$routeTable}.*")
            ->addSelect([
                'consultation_queue_number' => $queueNumberSubquery,
                'consultation_queue_created_at' => $queueCreatedSubquery,
            ])
            ->with([
                'visit.patient',
                'visit.medicalRecord',
                'visit.queueEntries.department',
                'department',
                'service',
                'routeServices.service',
                'services',
                'doctor',
                'mainDoctor',
                'primaryNurse',
                'emergencyCase',
                'emergencySession',
            ])
            ->whereIn('status', [
                VisitConsultationRoute::STATUS_PENDING,
                VisitConsultationRoute::STATUS_ACTIVE,
                VisitConsultationRoute::STATUS_PAUSED,
            ])
            ->where(function ($routeQuery) {
                $routeQuery->where('session_type', VisitConsultationRoute::SESSION_TYPE_EMERGENCY)
                    ->orWhereHas('department', function ($departmentQuery) {
                        $departmentQuery->where('type', DepartmentType::CONSULTATION->value);
                    });
            })
            ->whereHas('visit', function ($visitQuery) {
                $visitQuery->where(function ($statusQuery) {
                    $statusQuery
                        ->where(function ($outpatientQuery) {
                            $outpatientQuery
                                ->where('visit_type', VisitType::OUTPATIENT->value)
                                ->whereIn('status', [
                                    VisitStatus::WAITING->value,
                                    VisitStatus::ACTIVE->value,
                                    VisitStatus::CONSULTING->value,
                                    VisitStatus::EMERGENCY->value,
                                ]);
                        })
                        ->orWhere(function ($nonOpdQuery) {
                            $nonOpdQuery
                                ->whereIn('visit_type', [
                                    VisitType::INPATIENT->value,
                                    VisitType::EMERGENCY->value,
                                ])
                                ->where('status', '!=', VisitStatus::DISCHARGED->value);
                        });
                });
            });

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && ! $user->hasAnyRole(['Super Admin', 'Admin']) && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        if (! empty($filters['search'])) {
            $query->whereHas('visit', fn ($visitQuery) => $visitQuery->search($filters['search']));
        }

        if (! empty($filters['visit_type'])) {
            $query->whereHas('visit', fn ($visitQuery) => $visitQuery->where('visit_type', $filters['visit_type']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereHas('visit', fn ($visitQuery) => $visitQuery->whereDate('visit_date', '>=', $filters['date_from']));
        }

        if (! empty($filters['date_to'])) {
            $query->whereHas('visit', fn ($visitQuery) => $visitQuery->whereDate('visit_date', '<=', $filters['date_to']));
        }

        if ($request->boolean('my_patients')) {
            $query->where(function ($routeQuery) {
                $routeQuery->where('doctor_id', Auth::id())
                    ->orWhere('main_doctor_id', Auth::id())
                    ->orWhereHas('contributors', fn ($contributors) => $contributors->where('user_id', Auth::id()));
            });
        }

        $routes = $query
            ->orderByRaw('consultation_queue_number IS NULL')
            ->orderBy('consultation_queue_number')
            ->orderBy('consultation_queue_created_at')
            ->orderByRaw("CASE {$routeTable}.status WHEN 'PENDING' THEN 0 WHEN 'ACTIVE' THEN 1 WHEN 'PAUSED' THEN 2 ELSE 3 END")
            ->orderBy("{$routeTable}.created_at")
            ->paginate((int) ($filters['per_page'] ?? 15));

        return view('consultations.index', compact('routes', 'filters'));
    }

    /**
     * Show the consultation interface for a visit.
     */
    public function show(Request $request, Visit $visit, ?VisitConsultationRoute $route = null)
    {
        if ($route && (int) $route->visit_id !== (int) $visit->id) {
            abort(404);
        }

        $sessions = $this->consultationSessionService->getAllSessionsForVisit($visit);
        $activeRoute = $sessions->firstWhere('status', VisitConsultationRoute::STATUS_ACTIVE);
        $selectedRoute = $route
            ?? $activeRoute
            ?? ($sessions->count() === 1 ? $sessions->first() : null);

        $selectedRoute?->loadMissing([
            'department',
            'routeServices.service',
            'doctor',
            'mainDoctor',
            'primaryNurse',
            'emergencySession.mainDoctor',
            'emergencySession.primaryNurse',
            'emergencySession.contributors.user',
            'emergencyCase.assignedDoctor',
            'emergencyCase.assignedNurse',
            'emergencyCase.triagedBy',
            'emergencyCase.disposedBy',
            'emergencyCase.latestVitals.recordedBy',
            'emergencyCase.vitals.recordedBy',
            'emergencyCase.notes.creator',
            'emergencyCase.medicationOrders.frequency',
            'emergencyCase.medicationOrders.prescriber',
            'emergencyCase.labRequests.items',
            'emergencyCase.labRequests.targetDepartment',
            'emergencyCase.procedureRequests.service',
            'emergencyCase.procedureRequests.department',
            'emergencyCase.consumableUsages.product',
            'emergencyCase.consumableUsages.invoiceItem',
        ]);

        $routeSelectorRequired = ! $selectedRoute && $sessions->count() > 1;
        $record = $selectedRoute
            ? $this->consultationSessionService->getOrCreateMedicalRecordForRoute($selectedRoute, Auth::user())
            : null;

        $data = $this->consultationService->getConsultationData($visit, $record, ! $routeSelectorRequired);

        // Load tasks on the record
        if ($data['record']) {
            $data['record']->load([
                'tasks.assignedUser', 'tasks.creator', 'tasks.completedBy',
                'historiesOfPresentingComplaint.creator',
                'physicalExaminations.creator',
                'consultationRoute.contributors.user',
            ]);
        }

        $consultationSummary = $this->summaryService->forRecord($data['record']);

        // Get recent/popular patterns for the doctor
        $patterns = MedicalPattern::active()
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
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        // Drugs for prescription dropdown
        $drugs = Drug::where('is_active', true)->orderBy('name')->get(['id', 'name', 'generic_name', 'strength', 'dosage_form', 'unit']);

        $procedures = Procedure::with('department')->active()->orderBy('name')->get();
        $patientProcedures = PatientProcedure::with(['procedure.department', 'performedByUser'])
            ->where('visit_id', $visit->id)
            ->latest('scheduled_date')
            ->get();

        // Theatre / new procedure workflow
        $procedureRequestService = app(ProcedureRequestService::class);
        $procedureRequests = $procedureRequestService->forVisit($visit->id);
        $procedureDepartments = $procedureRequestService->procedureDepartments();
        $consultationDepartments = Department::active()
            ->where('type', DepartmentType::CONSULTATION->value)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
        $consultationServices = ServiceCatalog::active()
            ->with('department')
            ->where(function ($query) {
                $query->where('category', ServiceType::CONSULTATION->value)
                    ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('type', DepartmentType::CONSULTATION->value));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'category']);
        $followUpAppointment = $selectedRoute
            ? Appointment::with(['department', 'doctor', 'services', 'createdByUser'])
                ->where('consultation_route_id', $selectedRoute->id)
                ->whereNotIn('status', [
                    AppointmentStatus::CANCELLED->value,
                    AppointmentStatus::NO_SHOW->value,
                ])
                ->orderByDesc('appointment_date')
                ->orderByDesc('id')
                ->first()
            : null;
        $nextPatientInLine = ($selectedRoute && Auth::user()?->can('consultations.create'))
            ? app(ConsultationNextPatientService::class)->preview($selectedRoute, Auth::user())
            : null;

        return view('consultations.show', [
            'visit' => $data['visit'],
            'record' => $data['record'],
            'sessions' => $sessions,
            'selectedRoute' => $selectedRoute,
            'routeSelectorRequired' => $routeSelectorRequired,
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
            'consultationDepartments' => $consultationDepartments,
            'consultationServices' => $consultationServices,
            'followUpAppointment' => $followUpAppointment,
            'nextPatientInLine' => $nextPatientInLine,
            'consultationSummary' => $consultationSummary,
            'entryPermissions' => $this->entryPermissions,
        ]);
    }

    /**
     * Show the visit's full clinical consultation summary as a document.
     */
    public function history(Visit $visit)
    {
        $visit->load([
            'patient',
            'department',
            'visitInsurance.insuranceProvider',
            'vitals' => fn ($q) => $q->with('recordedBy')->latest(),
        ]);

        $sessions = $visit->consultationRoutes()
            ->with([
                'department',
                'doctor',
                'mainDoctor',
                'primaryNurse',
                'emergencyCase',
                'emergencySession',
            ])
            ->orderByRaw("CASE status WHEN 'ACTIVE' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'PAUSED' THEN 2 WHEN 'COMPLETED' THEN 3 ELSE 4 END")
            ->oldest()
            ->get();

        // Build a normalized summary per session (or per legacy record).
        $sessionSummaries = collect();

        if ($sessions->isNotEmpty()) {
            foreach ($sessions as $session) {
                $record = $session->medicalRecord;
                $sessionSummaries->push([
                    'session' => $session,
                    'record' => $record,
                    'summary' => $this->summaryService->forRecord($record),
                ]);
            }
        } else {
            $sessionSummaries->push([
                'session' => null,
                'record' => $visit->medicalRecord,
                'summary' => $this->summaryService->forRecord($visit->medicalRecord),
            ]);
        }

        $labRequests = $this->labService->getVisitLabRequests($visit);
        $procedureRequests = app(ProcedureRequestService::class)->forVisit($visit->id);

        // Visit-wide contributor roll-up across all sessions + lab/procedure owners.
        $contributors = $this->buildVisitContributors($sessionSummaries, $sessions, $labRequests, $procedureRequests);

        return view('consultations.history', [
            'visit' => $visit,
            'sessions' => $sessions,
            'sessionSummaries' => $sessionSummaries,
            'labRequests' => $labRequests,
            'procedureRequests' => $procedureRequests,
            'contributors' => $contributors,
            'generatedAt' => now(),
        ]);
    }

    /**
     * Aggregate every doctor/user who contributed to the visit, with their entry counts.
     *
     * @return array<int, array{user_id: int|null, name: string, role_label: string, entries: int}>
     */
    private function buildVisitContributors($sessionSummaries, $sessions, $labRequests, $procedureRequests): array
    {
        $mainDoctorIds = $sessions->pluck('doctor.id')->filter()->unique()->all();
        $tally = [];

        $bump = function (?int $id, ?string $name, bool $isMain) use (&$tally): void {
            if (! $name) {
                return;
            }
            $key = $id ? 'u-'.$id : 'n-'.$name;
            if (! isset($tally[$key])) {
                $tally[$key] = [
                    'user_id' => $id,
                    'name' => $name,
                    'role_label' => $isMain ? 'Main Doctor' : 'Contributor',
                    'entries' => 0,
                ];
            } elseif ($isMain) {
                $tally[$key]['role_label'] = 'Main Doctor';
            }
            $tally[$key]['entries']++;
        };

        foreach ($sessionSummaries as $bundle) {
            foreach ($bundle['summary']['sections'] ?? [] as $entries) {
                foreach ($entries as $entry) {
                    $id = $entry['owner_id'] ?? null;
                    $name = $entry['entered_by'] ?? null;
                    if ($name === 'Unknown user') {
                        continue;
                    }
                    $bump($id, $name, $id && in_array($id, $mainDoctorIds, true));
                }
            }
        }

        foreach ($labRequests as $req) {
            $owner = $req->requestedBy ?? null;
            $bump($owner?->id, $owner?->full_name, $owner && in_array($owner->id, $mainDoctorIds, true));
        }

        foreach ($procedureRequests as $pr) {
            $owner = $pr->requestingDoctor ?? $pr->requestedBy ?? null;
            $bump($owner?->id, $owner?->full_name, $owner && in_array($owner->id, $mainDoctorIds, true));
        }

        return array_values($tally);
    }

    public function summaryFragment(Request $request, Visit $visit)
    {
        $route = $this->consultationSessionService->resolveRouteForVisit(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );

        $record = $route ? $route->medicalRecord : $visit->medicalRecord;
        $consultationSummary = $this->summaryService->forRecord($record);

        return view('consultations.partials.summary-sections', compact('consultationSummary'));
    }

    public function storeRoute(Request $request, Visit $visit)
    {
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
            if ($request->boolean('route_only')) {
                $route = $this->consultationRouteService->activateRouteOnly($route, Auth::user());

                return redirect()
                    ->route('admin.visits.show', $visit)
                    ->with('success', __('messages.consultations.route_activated_queued'));
            }

            $route = $this->consultationRouteService->activateRoute($route, Auth::user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.route_activated'));
    }

    public function completeRoute(Request $request, Visit $visit, VisitConsultationRoute $route)
    {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $route = $this->consultationRouteService->completeRoute($route, Auth::user(), $data['notes'] ?? null);

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.route_completed'));
    }

    public function cancelRoute(Request $request, Visit $visit, VisitConsultationRoute $route)
    {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $route = $this->consultationRouteService->cancelRoute($route, Auth::user(), $data['reason'] ?? null);

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.route_cancelled'));
    }

    public function storeFollowUpAppointment(
        Request $request,
        Visit $visit,
        VisitConsultationRoute $route,
        ConsultationFollowUpService $followUps,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $this->validateFollowUpAppointment($request);
        $record = $this->consultationSessionService->getOrCreateMedicalRecordForRoute($route, Auth::user());

        try {
            $followUps->create($visit, $route, $record, $data, Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', __('messages.consultations.followup_saved'));
    }

    public function updateFollowUpAppointment(
        Request $request,
        Visit $visit,
        VisitConsultationRoute $route,
        Appointment $appointment,
        ConsultationFollowUpService $followUps,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $this->validateFollowUpAppointment($request);
        $record = $this->consultationSessionService->getOrCreateMedicalRecordForRoute($route, Auth::user());

        try {
            $followUps->update($appointment, $visit, $route, $record, $data, Auth::user());
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
        ConsultationFollowUpService $followUps,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $followUps->cancel($appointment, $visit, $route, $data['reason'], Auth::user());
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
        ConsultationNextPatientService $nextPatients,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        try {
            $nextRoute = $nextPatients->openNext($route, Auth::user(), false);
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
        ConsultationNextPatientService $nextPatients,
    ) {
        $this->abortIfRouteMismatch($visit, $route);

        try {
            $nextRoute = $nextPatients->openNext($route, Auth::user(), true);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.consultations.routes.show', [$nextRoute->visit, $nextRoute])
            ->with('success', __('messages.consultations.completed_next_opened'));
    }

    private function validateFollowUpAppointment(Request $request): array
    {
        return $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'department_id' => ['required', 'exists:departments,id'],
            'service_id' => ['nullable', 'exists:service_catalog,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'notify_patient' => ['nullable', 'boolean'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complaint CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

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

        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );
        $complaint = $this->consultationService->addComplaint($record, $request->only('complaint_catalogue_id', 'description', 'duration', 'duration_unit', 'severity', 'notes'));

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

        $complaint = $this->consultationService->updateComplaint($complaint, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'complaint' => $this->entryPayload($complaint)]);
        }

        return back()->withFragment('complaints-section')->with('success', __('messages.consultations.complaint_updated'));
    }

    public function destroyComplaint(Complaint $complaint)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $complaint), 403);

        $this->consultationService->deleteComplaint($complaint);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.complaint_removed'));
    }

    /*
    |--------------------------------------------------------------------------
    | History of Presenting Complaint CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

    public function storeHistoryOfPresentingComplaint(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'complaint_id' => ['nullable', 'exists:complaints,id'],
            'content' => ['required_without_all:onset,duration,location,character,radiation,associated_symptoms,aggravating_factors,relieving_factors,severity,timing,notes', 'nullable', 'string', 'max:8000'],
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

        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );

        $entry = $this->hopcService->create($record, $data, Auth::user());

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
            'content' => ['required_without_all:onset,duration,location,character,radiation,associated_symptoms,aggravating_factors,relieving_factors,severity,timing,notes', 'nullable', 'string', 'max:8000'],
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

        $old = $hopc->getOriginal();
        $hopc->update(array_merge($data, ['updated_by' => Auth::id()]));
        app(MedicalRecordEntryLogService::class)->updated($hopc, $old, Auth::user());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'hopc' => $this->entryPayload($hopc, ['complaint'])]);
        }

        return back()->withFragment('hopc-section')->with('success', __('messages.consultations.hopc_updated'));
    }

    public function destroyHistoryOfPresentingComplaint(HistoryOfPresentingComplaint $hopc)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $hopc), 403);

        app(MedicalRecordEntryLogService::class)->deleted($hopc, Auth::user());
        $hopc->delete();

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->withFragment('hopc-section')->with('success', __('messages.consultations.hopc_removed'));
    }

    /*
    |--------------------------------------------------------------------------
    | Examination CRUD (AJAX)
    |--------------------------------------------------------------------------
    */

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

        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );

        $entry = $this->examinationService->create($record, $data, Auth::user());

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

        $old = $examination->getOriginal();
        $examination->update(array_merge($data, ['updated_by' => Auth::id()]));
        app(MedicalRecordEntryLogService::class)->updated($examination, $old, Auth::user());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'examination' => $this->entryPayload($examination)]);
        }

        return back()->withFragment('examination-section')->with('success', __('messages.consultations.examination_updated'));
    }

    public function destroyExamination(PhysicalExamination $examination)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $examination), 403);

        app(MedicalRecordEntryLogService::class)->deleted($examination, Auth::user());
        $examination->delete();

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->withFragment('examination-section')->with('success', __('messages.consultations.examination_removed'));
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

        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );
        $diagnosis = $this->consultationService->addDiagnosis($record, $request->only('description', 'icd_code', 'icd_code_id', 'type', 'notes'));

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'diagnosis' => $this->entryPayload($diagnosis, ['icdCodeEntry'])]);
        }

        return back()->with('success', __('messages.consultations.diagnosis_added'));
    }

    public function destroyDiagnosis(Diagnosis $diagnosis)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $diagnosis), 403);

        $this->consultationService->deleteDiagnosis($diagnosis);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.diagnosis_removed'));
    }

    /**
     * Toggle a diagnosis type between provisional and final.
     */
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

        $diagnosis = $this->consultationService->updateDiagnosis($diagnosis, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'type' => $diagnosis->fresh()->type,
                'diagnosis' => $this->entryPayload($diagnosis, ['icdCodeEntry']),
            ]);
        }

        return back()->with('success', __('messages.consultations.diagnosis_updated'));
    }

    /**
     * Set a diagnosis as the primary one for this record.
     */
    public function setPrimaryDiagnosis(Diagnosis $diagnosis)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canEdit(Auth::user(), $diagnosis), 403);

        $this->consultationService->setPrimaryDiagnosis($diagnosis);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.primary_diagnosis_set'));
    }

    /**
     * Return active services for a department (for investigation dept dropdown).
     */
    public function getDepartmentServices(Request $request, Department $department)
    {
        $services = $department->services()
            ->with('prices')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price']);

        $visit = $request->integer('visit_id')
            ? Visit::with('visitInsurance.insuranceProvider')->find($request->integer('visit_id'))
            : null;

        if ($visit) {
            $services = $services->map(function (ServiceCatalog $service) use ($visit) {
                $pricing = $this->priceResolver->resolveForVisit($service, $visit);

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'price' => $pricing['selected_price'],
                    'cash_price' => $pricing['cash_price'],
                    'selected_price' => $pricing['selected_price'],
                    'payer_type' => $pricing['payer_type'],
                    'pricing_source' => $pricing['pricing_source'],
                ];
            })->values();
        }

        return response()->json($services);
    }

    /**
     * Return investigation metadata for a department (result_type + catalog tests if applicable).
     */
    public function getDepartmentInvestigationInfo(Department $department)
    {
        $resultType = $department->result_type ?? ResultType::NONE;

        $data = [
            'result_type' => $resultType->value,
            'uses_catalog' => $resultType->usesTestCatalog(),
            'label' => $resultType->label(),
            'lab_tests' => [],
        ];

        if ($resultType->usesTestCatalog()) {
            $data['lab_tests'] = LabTest::with('criteria')
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
            'department_id' => ['nullable', 'exists:departments,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:service_catalog,id'],
            'investigation_type' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );
        $created = [];
        $labRequest = null;

        if (! empty($request->service_ids)) {
            $services = ServiceCatalog::whereIn('id', $request->service_ids)->get();

            foreach ($services as $service) {
                $created[] = $this->consultationService->addInvestigation($record, [
                    'investigation_type' => $service->name,
                    'description' => $service->description ?? $service->name,
                    'urgency' => $request->urgency ?? 'routine',
                    'notes' => $request->notes,
                ]);
            }

            // ALSO create a LabRequest so the investigation department sees it on their queue.
            $items = $services->map(fn ($s) => [
                'service_id' => $s->id,
                'name' => $s->name,
                'status' => 'pending',
            ])->all();

            if (! empty($items)) {
                $labRequest = $this->labService->createRequest($visit, $items, [
                    'target_department_id' => $request->department_id,
                    'clinical_info' => $request->notes,
                    'urgency' => $request->urgency ?? 'routine',
                ]);
            }
        } elseif (! empty($request->investigation_type)) {
            $created[] = $this->consultationService->addInvestigation($record, [
                'investigation_type' => $request->investigation_type,
                'description' => $request->description ?? $request->investigation_type,
                'urgency' => $request->urgency ?? 'routine',
                'notes' => $request->notes,
            ]);

            // Create a free-text LabRequest for non-catalogue requests
            $labRequest = $this->labService->createRequest($visit, [$request->investigation_type], [
                'target_department_id' => $request->department_id,
                'clinical_info' => $request->description ?? $request->notes,
                'urgency' => $request->urgency ?? 'routine',
            ]);
        }

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

        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );
        $treatment = $this->consultationService->addTreatment($record, $request->only('type', 'description'));

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

        $treatment = $this->consultationService->updateTreatment($treatment, $data);

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'treatment' => $this->entryPayload($treatment)]);
        }

        return back()->withFragment('treatments-section')->with('success', __('messages.consultations.treatment_updated'));
    }

    public function destroyTreatment(Treatment $treatment)
    {
        abort_unless(Auth::user() && $this->entryPermissions->canDelete(Auth::user(), $treatment), 403);

        $this->consultationService->deleteTreatment($treatment);

        if ($this->shouldReturnJson(request())) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.consultations.treatment_removed'));
    }

    /*
    |--------------------------------------------------------------------------
    | Prescription
    |--------------------------------------------------------------------------
    */

    public function storePrescription(StorePrescriptionRequest $request, Visit $visit)
    {
        $record = $this->consultationService->getOrCreateRecord(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );
        $prescription = $this->prescriptionService->create($record, $request->validated());

        if ($this->shouldReturnJson($request)) {
            return response()->json(['success' => true, 'prescription' => $prescription->load(['items', 'creator', 'doctor', 'updater', 'sourcePattern'])]);
        }

        return redirect()
            ->route('admin.consultations.show', $visit)
            ->withFragment('prescriptions-section')
            ->with('success', __('messages.consultations.prescription_created', ['number' => $prescription->prescription_number]));
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

    public function storeProcedureRequest(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'service_catalog_id' => ['required', 'exists:service_catalog,id'],
            'procedure_id' => ['nullable', 'exists:procedures,id'],
            'priority' => ['required', 'in:routine,urgent,emergency'],
            'indication' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'preferred_datetime' => ['nullable', 'date'],
        ]);
        $data['visit_id'] = $visit->id;

        try {
            $procedureRequest = app(ProcedureRequestService::class)
                ->requestProcedure($data, Auth::user());
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

    /**
     * Return complaint catalogue suggestions.
     */
    public function suggestComplaints(Request $request, ComplaintSearchService $complaintSearch)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json($complaintSearch->autocompletePayload($complaintSearch->search($q, 10)));
    }

    /**
     * Return diagnosis description suggestions from existing diagnoses.
     */
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

    /*
    |--------------------------------------------------------------------------
    | Lab Request from Consultation
    |--------------------------------------------------------------------------
    */

    public function storeLabRequest(Request $request, Visit $visit)
    {
        $request->validate([
            'target_department_id' => ['required', 'exists:departments,id'],
            'items' => ['required', 'array', 'min:1'],
            // items can be test IDs (int) or free-text names (string)
            'urgency' => ['nullable', 'in:routine,urgent,emergency'],
            'clinical_info' => ['nullable', 'string', 'max:2000'],
        ]);

        $labRequest = $this->labService->createRequest(
            $visit,
            $request->input('items', []),
            $request->only('target_department_id', 'urgency', 'clinical_info')
        );

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

        if (! $visit->canTransitionTo($newStatus)) {
            return back()->with('error', __('messages.visits.cannot_transition', ['from' => $visit->status->label(), 'to' => $newStatus->label()]));
        }

        $this->visitService->transition($visit, $newStatus, $request->notes);

        return redirect()
            ->route('admin.consultations.index')
            ->with('success', __('messages.consultations.visit_transitioned', ['status' => $newStatus->label()]));
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
            'service_id' => ['nullable', 'exists:service_catalog,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:service_catalog,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'activate_now' => ['nullable', 'boolean'],
        ]);

        try {
            $serviceIds = collect($request->input('service_ids', []))
                ->when($request->filled('service_id'), fn ($ids) => $ids->push((int) $request->service_id))
                ->filter()
                ->unique()
                ->values();

            $route = $this->consultationRouteService->sendToAnotherConsultation(
                visit: $visit,
                department: Department::findOrFail((int) $request->department_id),
                service: $serviceIds->isNotEmpty()
                    ? ServiceCatalog::whereIn('id', $serviceIds)->get()
                    : null,
                doctor: $request->filled('doctor_id') ? User::findOrFail((int) $request->doctor_id) : null,
                routedBy: Auth::user(),
                notes: $request->notes,
                activateNow: $request->boolean('activate_now'),
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $dept = Department::find($request->department_id);

        return redirect()
            ->route('admin.consultations.routes.show', [$visit, $route])
            ->with('success', $request->boolean('activate_now')
                ? __('messages.consultations.referred_activated', ['department' => $dept?->name])
                : __('messages.consultations.referred_queued', ['department' => $dept?->name]));
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
            'notes' => ['nullable', 'string', 'max:1000'],
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
