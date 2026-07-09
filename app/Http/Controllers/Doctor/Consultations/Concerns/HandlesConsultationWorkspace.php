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
use App\Services\Consultation\Specialty\ConsultationSpecialtyEntryService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyFavoriteService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyLayoutService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyOrderSetService;
use App\Services\Consultation\Specialty\ConsultationSpecialtyProfileResolver;
use App\Services\Consultation\Specialty\ConsultationSpecialtyReadinessService;
use App\Services\Consultation\Specialty\DoctorSpecialtyWorkspaceService;
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
        if ($user && ! $user->hasAnyRole(['Super Admin', 'Admin']) && $user->department_id) {
            $query->where('department_id', $user->department_id);
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
        $frequencyOptions = app(\App\Services\ClinicalFrequencyOptionService::class);

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
            ? $this->completionReadiness->forRoute($selectedRoute)
            : null;
        $consultationPreview = $this->buildConsultationPreviewData($visit);
        $specialtyContext = app(ConsultationSpecialtyProfileResolver::class)->resolve(
            user: $request->user(),
            visit: $visit,
            consultationRoute: $selectedRoute,
            department: $selectedRoute?->department ?? $visit->currentDepartment,
        );
        $specialtyLayout = app(ConsultationSpecialtyLayoutService::class)->buildLayout($specialtyContext);
        $specialtyEntryService = app(ConsultationSpecialtyEntryService::class);
        $specialtyEntries = $selectedRoute
            ? $specialtyEntryService->entriesAsArray($selectedRoute, $specialtyContext->profile)
            : [];
        $specialtyEntryGroups = $selectedRoute
            ? $specialtyEntryService->entriesGroupedForWorkspace($selectedRoute, $specialtyContext->profile)
            : [];
        $specialtyFavorites = app(ConsultationSpecialtyFavoriteService::class)->getWorkspaceDefaults($specialtyContext->profile);
        $specialtyOrderSets = app(ConsultationSpecialtyOrderSetService::class)->getWorkspaceOrderSets($specialtyContext);
        $specialtyReadiness = $selectedRoute
            ? app(ConsultationSpecialtyReadinessService::class)->evaluate($selectedRoute, $specialtyContext, ['completionReadiness' => $completionReadiness])
            : null;
        $reopenEligibility = ($selectedRoute && Auth::user())
            ? app(\App\Services\Consultation\ConsultationReopenEligibilityService::class)->canReopen(Auth::user(), $visit, $selectedRoute)
            : null;
        $specialtySummaryBuilder = $selectedRoute ? [
            'available' => true,
            'profile_code' => $specialtyContext->profile->code,
            'preview_url' => route('admin.consultations.specialty-summary.preview', $visit),
        ] : ['available' => false];
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
            'followUpAppointment' => $followUpAppointment,
            'nextPatientInLine' => $nextPatientInLine,
            'completionReadiness' => $completionReadiness,
            'consultationSummary' => $consultationSummary,
            'consultationPreview' => $consultationPreview,
            'specialtyContext' => $specialtyContext->toArray(),
            'specialtyLayout' => $specialtyLayout,
            'specialtyEntries' => $specialtyEntries,
            'specialtyEntryGroups' => $specialtyEntryGroups,
            'specialtyFavorites' => $specialtyFavorites,
            'specialtyOrderSets' => $specialtyOrderSets,
            'specialtyReadiness' => $specialtyReadiness,
            'reopenEligibility' => $reopenEligibility,
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

    private function buildConsultationPreviewData(Visit $visit): array
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

        return [
            'visit' => $visit,
            'sessions' => $sessions,
            'sessionSummaries' => $sessionSummaries,
            'labRequests' => $labRequests,
            'procedureRequests' => $procedureRequests,
            'contributors' => $contributors,
            'generatedAt' => now(),
        ];
    }

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
