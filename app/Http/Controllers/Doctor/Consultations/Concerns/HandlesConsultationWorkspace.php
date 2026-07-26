<?php

namespace App\Http\Controllers\Doctor\Consultations\Concerns;

use App\Enums\AppointmentStatus;
use App\Enums\DepartmentType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Drug;
use App\Models\Investigation;
use App\Models\MedicalPattern;
use App\Models\PatientProcedure;
use App\Models\Prescription;
use App\Models\Procedure;
use App\Models\QueueEntry;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ClinicalFrequencyOptionService;
use App\Services\Consultation\ConsultationActionException;
use App\Services\Consultation\ConsultationReopenEligibilityService;
use App\Services\Consultation\ConsultationSessionEligibilityService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyEntryService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyFavoriteService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyOrderSetService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;
use App\Services\Consultation\Specialty\DoctorSpecialtyWorkspaceService;
use App\Services\ConsultationNextPatientService;
use App\Services\Consultation\Maternity\ConsultationMaternityHandoffPresenter;
use App\Services\Consultation\Maternity\ConsultationMaternityModalPresenter;
use App\Services\Consultation\Maternity\GynaecologyConsultationContextService;
use App\Services\Consultation\Maternity\ObstetricConsultationContextService;
use App\Services\ConsultationPreviewDataService;
use App\Services\InpatientWorkspaceScope;
use App\Services\MedicalRecordEntryLogService;
use App\Services\ProcedureRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait HandlesConsultationWorkspace
{
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
        if (! $request->routeIs('inpatient.*') && ! $request->hasAny(['search', 'visit_type', 'date_from', 'date_to', 'date_range', 'my_patients'])) {
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
                'visit.admission',
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
                VisitConsultationRoute::STATUS_COMPLETED,
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
                                ->where(function ($opdStatusQuery) {
                                    $opdStatusQuery
                                        ->whereIn('status', [
                                            VisitStatus::WAITING->value,
                                            VisitStatus::ACTIVE->value,
                                            VisitStatus::CONSULTING->value,
                                            VisitStatus::EMERGENCY->value,
                                        ])
                                        ->orWhere(function ($completedTodayQuery) {
                                            $completedTodayQuery
                                                ->where('status', VisitStatus::COMPLETED->value)
                                                ->whereDate('completed_at', today());
                                        });
                                });
                        })
                        ->orWhere(function ($nonOpdQuery) {
                            $nonOpdQuery
                                ->whereIn('visit_type', [
                                    VisitType::INPATIENT->value,
                                    VisitType::EMERGENCY->value,
                                ])
                                ->where(function ($nonOpdStatusQuery) {
                                    $nonOpdStatusQuery
                                        ->where('status', '!=', VisitStatus::DISCHARGED->value)
                                        ->orWhereHas('admission', fn ($admissionQuery) => $admissionQuery
                                            ->whereIn('status', ['admitted', 'on_leave'])
                                            ->whereNull('actual_discharge_date'))
                                        ->orWhereHas('admission', fn ($admissionQuery) => $admissionQuery
                                            ->whereNotNull('actual_discharge_date')
                                            ->whereDate('actual_discharge_date', today()));
                                });
                        });
                });
            });

        $query->where(function ($routeStatusQuery) {
            $routeStatusQuery
                ->whereIn('status', [
                    VisitConsultationRoute::STATUS_PENDING,
                    VisitConsultationRoute::STATUS_ACTIVE,
                    VisitConsultationRoute::STATUS_PAUSED,
                ])
                ->orWhere(function ($completedRouteQuery) {
                    $completedRouteQuery
                        ->where('status', VisitConsultationRoute::STATUS_COMPLETED)
                        ->whereHas('visit', function ($visitQuery) {
                            $visitQuery
                                ->where(function ($opdQuery) {
                                    $opdQuery
                                        ->where('visit_type', VisitType::OUTPATIENT->value)
                                        ->where('status', VisitStatus::COMPLETED->value)
                                        ->whereDate('completed_at', today());
                                })
                                ->orWhereHas('admission', fn ($admissionQuery) => $admissionQuery
                                    ->whereNotNull('actual_discharge_date')
                                    ->whereDate('actual_discharge_date', today()));
                        });
                });
        });

        /** @var User|null $user */
        $user = Auth::user();
        if ($request->routeIs('doctor.*') && $user && ! $user->hasRole('Super Admin') && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        if ($request->routeIs('inpatient.*')) {
            $departmentId = app(InpatientWorkspaceScope::class)->departmentId();
            $filters['visit_type'] = VisitType::INPATIENT->value;
            $query->whereHas('visit.admission.bed.ward', fn ($ward) => $ward->where('department_id', $departmentId));
        }

        if (! empty($filters['search'])) {
            $query->whereHas('visit', fn ($visitQuery) => $visitQuery->search($filters['search']));
        }

        if (! empty($filters['visit_type'])) {
            $query->whereHas('visit', fn ($visitQuery) => $visitQuery->where('visit_type', $filters['visit_type']));
        }

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? null;
            $to = $filters['date_to'] ?? null;

            $query->whereHas('visit', function ($visitQuery) use ($from, $to) {
                $visitQuery->where(function ($dateQuery) use ($from, $to) {
                    $dateQuery->where(function ($visitDateQuery) use ($from, $to) {
                        $visitDateQuery
                            ->when($from, fn ($q) => $q->whereDate('visit_date', '>=', $from))
                            ->when($to, fn ($q) => $q->whereDate('visit_date', '<=', $to));
                    })
                        ->orWhereHas('admission', function ($admissionQuery) use ($from, $to) {
                            $admissionQuery
                                ->where(function ($activeQuery) {
                                    $activeQuery
                                        ->whereIn('status', ['admitted', 'on_leave'])
                                        ->whereNull('actual_discharge_date');
                                })
                                ->orWhere(function ($dischargeQuery) use ($from, $to) {
                                    $dischargeQuery
                                        ->whereNotNull('actual_discharge_date')
                                        ->when($from, fn ($q) => $q->whereDate('actual_discharge_date', '>=', $from))
                                        ->when($to, fn ($q) => $q->whereDate('actual_discharge_date', '<=', $to));
                                });
                        });
                });
            });
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

    public function show(Request $request, Visit $visit, ?VisitConsultationRoute $route = null)
    {
        if ($route && (int) $route->visit_id !== (int) $visit->id) {
            abort(404);
        }

        $sessions = $this->consultationSessionService->getAllSessionsForVisit($visit);
        $activeRoute = $sessions->firstWhere('status', VisitConsultationRoute::STATUS_ACTIVE);
        $requestedRoute = $route ? $sessions->firstWhere('id', $route->id) : null;
        $selectedRoute = $requestedRoute
            ?? $activeRoute
            ?? ($sessions->count() === 1 ? $sessions->first() : null);

        $visit->setRelation('consultationRoutes', $sessions);
        $visit->setRelation('activeConsultationRoute', $activeRoute);
        $visit->setRelation(
            'pendingConsultationRoutes',
            $sessions->where('status', VisitConsultationRoute::STATUS_PENDING)->values(),
        );

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
        $data['visit']->loadMissing([
            'admission.bed.ward',
            'patient.activeAdmission.bed.ward',
        ]);
        $consultationAdmission = ($data['visit']->admission?->is_active ?? false)
            ? $data['visit']->admission
            : $data['visit']->patient?->activeAdmission;

        // Load tasks on the record
        if ($data['record']) {
            $data['record']->loadMissing([
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
        $investigationRouteDepartments = $investigationDepts->isNotEmpty()
            ? $investigationDepts
            : Department::active()->orderBy('name')->get();

        // Doctors for task assignment
        $doctors = User::role('Doctor')->where('status', 'active')->orderBy('first_name')->get();

        // Drugs for prescription dropdown
        $drugs = Drug::where('is_active', true)->orderBy('name')->get(['id', 'name', 'generic_name', 'strength', 'dosage_form', 'unit']);
        $frequencyOptions = app(ClinicalFrequencyOptionService::class);

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
        $referralDepts = Department::active()
            ->where('id', '!=', $visit->current_department_id)
            ->where('type', DepartmentType::CONSULTATION->value)
            ->orderBy('name')
            ->get();
        $referralServicesPayloadByDept = ServiceCatalog::where('is_active', true)
            ->where('category', ServiceType::CONSULTATION->value)
            ->whereIn('department_id', $referralDepts->pluck('id'))
            ->orderBy('name')
            ->get()
            ->groupBy('department_id')
            ->map(fn ($services) => $services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category,
            ])->values())
            ->all();
        $pharmacyFallbackDepartment = Department::active()
            ->where('type', DepartmentType::PHARMACY->value)
            ->orderBy('name')
            ->first();
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
        $completionReadiness = $selectedRoute
            ? $this->completionReadiness->forRoute($selectedRoute, refresh: false)
            : null;
        $consultationPreview = $this->buildConsultationPreviewData(
            $visit,
            $sessions,
            $labRequests,
            $procedureRequests,
            $selectedRoute ? [$selectedRoute->id => $consultationSummary] : [],
        );
        $specialtyContext = app(ConsultationSpecialtyProfileResolver::class)->resolve(
            user: $request->user(),
            visit: $visit,
            consultationRoute: $selectedRoute,
            department: $selectedRoute?->department ?? $visit->currentDepartment,
        );
        $specialtyLayout = app(ConsultationSpecialtyLayoutService::class)->buildLayout($specialtyContext);
        $specialtyEntryService = app(ConsultationSpecialtyEntryService::class);
        $specialtyEntryModels = $selectedRoute
            ? $specialtyEntryService->getEntriesForConsultation($selectedRoute, $specialtyContext->profile)
            : new \Illuminate\Database\Eloquent\Collection();
        $specialtyEntries = $specialtyEntryService->entriesAsArrayFrom($specialtyEntryModels);
        $specialtyEntryGroups = $specialtyEntryService->entriesGroupedForWorkspaceFrom($specialtyEntryModels);
        $specialtyFavorites = app(ConsultationSpecialtyFavoriteService::class)->getWorkspaceDefaults($specialtyContext->profile);
        $specialtyOrderSets = app(ConsultationSpecialtyOrderSetService::class)->getWorkspaceOrderSets($specialtyContext);
        $specialtyReadiness = $selectedRoute
            ? app(ConsultationSpecialtyReadinessService::class)->evaluate($selectedRoute, $specialtyContext, ['completionReadiness' => $completionReadiness])
            : null;
        $reopenEligibility = ($selectedRoute && Auth::user())
            ? app(ConsultationReopenEligibilityService::class)->canReopen(Auth::user(), $visit, $selectedRoute)
            : null;
        $sessionEligibilityActions = Auth::user()
            ? app(ConsultationSessionEligibilityService::class)->availableActionsFor($visit, $selectedRoute, Auth::user())
            : [];
        $specialtySummaryBuilder = $selectedRoute ? [
            'available' => true,
            'profile_code' => $specialtyContext->profile->code,
            'preview_url' => route('admin.consultations.specialty-summary.preview', $visit),
        ] : ['available' => false];

        // Phase 14R.3.1 — maternity context for the Obstetrics workspace.
        // build() short-circuits to a disabled view model (no resolver call, no
        // maternity queries) unless the workspace flag is on AND the resolved
        // specialty profile is Obstetrics. Resolved exactly once per request;
        // the ribbon and panel share this instance.
        $maternityContext = $selectedRoute
            ? app(ObstetricConsultationContextService::class)->build(
                $selectedRoute,
                $specialtyContext->profile,
                $request->user(),
                url()->current(),
            )
            : \App\Data\Consultation\Maternity\ObstetricWorkspaceViewModel::disabled();

        // Phase 14R.4.1 — Gynaecology pregnancy context. Explicit-only: the
        // service short-circuits to a disabled model (no resolver call, no
        // link/profile queries) unless the Gynaecology context flag is on AND
        // the resolved profile is Gynaecology. Built once; the card, the
        // obstetric-history projections and the order-set CTAs share it.
        $gynaecologyContext = $selectedRoute
            ? app(GynaecologyConsultationContextService::class)->build(
                $selectedRoute,
                $specialtyContext->profile,
                $request->user(),
                url()->current(),
            )
            : \App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel::disabled();

        // Phase 14R.5 — operational handoff actions (Consultation → Admission
        // Request, Gynaecology → Obstetrics referral, Postnatal review).
        // Returns ['enabled' => false] with zero queries unless
        // MATERNITY_CONSULTATION_HANDOFFS_ENABLED is on and the resolved
        // profile is Obstetrics or Gynaecology.
        $maternityHandoffs = app(ConsultationMaternityHandoffPresenter::class)->build(
            $selectedRoute,
            $specialtyContext->profile,
            $request->user(),
        );

        // Phase 14R.5.1 — typed actions for the Obstetrics panel (14R.3.1) and
        // Gynaecology card (14R.4.1), whose triggers shipped without modal
        // bodies. Reuses the view models built above; performs no extra
        // permission lookups and no queries when those flags are off.
        $maternityModalPresenter = app(ConsultationMaternityModalPresenter::class);
        $obstetricActions = $maternityModalPresenter->obstetrics(
            $maternityContext, $selectedRoute, $request->user()
        );
        $gynaecologyActions = $maternityModalPresenter->gynaecology(
            $gynaecologyContext, $selectedRoute, $request->user()
        );
        $doctorSpecialtyWorkspace = app(DoctorSpecialtyWorkspaceService::class)->build(
            $request->user(),
            $selectedRoute,
            $specialtyContext,
            [
                'specialtyReadiness' => $specialtyReadiness,
                'specialtyOrderSets' => $specialtyOrderSets,
                'specialtySummaryBuilder' => $specialtySummaryBuilder,
            ],
        );
        $frequencyDefaults = $specialtyFavorites['frequency_defaults'] ?? $frequencyOptions->options();

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
            'investigationRouteDepartments' => $investigationRouteDepartments,
            'doctors' => $doctors,
            'drugs' => $drugs,
            'procedures' => $procedures,
            'patientProcedures' => $patientProcedures,
            'procedureRequests' => $procedureRequests,
            'procedureDepartments' => $procedureDepartments,
            'consultationDepartments' => $consultationDepartments,
            'consultationServices' => $consultationServices,
            'referralDepts' => $referralDepts,
            'referralServicesPayloadByDept' => $referralServicesPayloadByDept,
            'pharmacyFallbackDepartment' => $pharmacyFallbackDepartment,
            'followUpAppointment' => $followUpAppointment,
            'nextPatientInLine' => $nextPatientInLine,
            'completionReadiness' => $completionReadiness,
            'consultationSummary' => $consultationSummary,
            'consultationAdmission' => $consultationAdmission,
            'consultationPreview' => $consultationPreview,
            'specialtyContext' => $specialtyContext->toArray(),
            'specialtyLayout' => $specialtyLayout,
            // Phase 14R.3.1 — single prepared maternity view model shared by the
            // ribbon and the context panel. Built once here; the service returns
            // a disabled model (and never calls the resolver) unless the
            // workspace flag is on AND the profile is Obstetrics.
            'maternityContext' => $maternityContext,
            'gynaecologyContext' => $gynaecologyContext,
            'maternityHandoffs' => $maternityHandoffs,
            'obstetricMaternityActions' => $obstetricActions,
            'gynaecologyMaternityActions' => $gynaecologyActions,
            'specialtyEntries' => $specialtyEntries,
            'specialtyEntryGroups' => $specialtyEntryGroups,
            'specialtyFavorites' => $specialtyFavorites,
            'specialtyOrderSets' => $specialtyOrderSets,
            'specialtyReadiness' => $specialtyReadiness,
            'reopenEligibility' => $reopenEligibility,
            'sessionEligibilityActions' => $sessionEligibilityActions,
            'specialtySummaryBuilder' => $specialtySummaryBuilder,
            'doctorSpecialtyWorkspace' => $doctorSpecialtyWorkspace,
            'entryPermissions' => $this->entryPermissions,
            'prescriptionFrequencyOptions' => $frequencyDefaults,
            'taskFrequencyOptions' => $frequencyDefaults,
            'frequencyDoseMap' => $frequencyOptions->doseMap(),
        ]);
    }

    public function history(Visit $visit)
    {
        return view('consultations.history', $this->buildConsultationPreviewData($visit));
    }

    private function buildConsultationPreviewData(
        Visit $visit,
        $sessions = null,
        $labRequests = null,
        $procedureRequests = null,
        array $sessionSummaries = [],
    ): array
    {
        return app(ConsultationPreviewDataService::class)->build(
            $visit,
            $sessions,
            $labRequests,
            $procedureRequests,
            $sessionSummaries,
        );
    }

    public function summaryFragment(Request $request, Visit $visit)
    {
        $payload = $this->workspacePayloads->summaryPayload(
            $visit,
            $request->integer('consultation_route_id') ?: null,
        );

        $consultationSummary = $payload['consultationSummary'];

        return view('consultations.partials.summary-sections', compact('consultationSummary'));
    }

    public function readinessFragment(Request $request, Visit $visit)
    {
        $routeId = $request->integer('consultation_route_id') ?: null;
        $selectedRoute = $routeId
            ? VisitConsultationRoute::query()
                ->where('visit_id', $visit->id)
                ->whereKey($routeId)
                ->first()
            : $visit->activeConsultationRoute()->first();

        if (! $selectedRoute) {
            return response('', 204);
        }

        $selectedRoute->load(['visit', 'department']);
        $completionReadiness = $this->completionReadiness->forRoute($selectedRoute);
        $specialtyContext = app(ConsultationSpecialtyProfileResolver::class)->resolve(
            user: $request->user(),
            visit: $visit,
            consultationRoute: $selectedRoute,
            department: $selectedRoute->department ?? $visit->currentDepartment,
        );
        $specialtyReadiness = app(ConsultationSpecialtyReadinessService::class)->evaluate(
            $selectedRoute,
            $specialtyContext,
            ['completionReadiness' => $completionReadiness],
        );

        return view('consultations.partials.completion-readiness-card', compact('completionReadiness', 'specialtyReadiness'));
    }

    public function updateFinalNote(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'final_note' => ['nullable', 'string', 'max:20000'],
        ]);

        try {
            $context = $this->consultationMutationContext($request, $visit, 'final_note.update', 'consultations.create');
        } catch (ConsultationActionException $e) {
            return $this->consultationActionFailureResponse($request, $e);
        }

        $record = $context->medicalRecord;
        $old = $record->getOriginal();
        $record->forceFill([
            'final_note' => $data['final_note'] ?? null,
            'final_note_updated_by' => Auth::id(),
            'final_note_updated_at' => now(),
        ])->save();

        app(MedicalRecordEntryLogService::class)->updated($record, $old, Auth::user());

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('consultations.final_note_saved'),
                'final_note' => $record->final_note,
            ]);
        }

        return back()->withFragment('summary-section')->with('success', __('consultations.final_note_saved'));
    }
}
