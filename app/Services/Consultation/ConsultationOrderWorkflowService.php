<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Enums\PrescriptionStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ResultType;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\Prescription;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteLog;
use App\Models\VisitDepartmentHistory;
use App\Services\ActivityLogService;
use App\Services\ConsultationService;
use App\Services\LabService;
use App\Services\ProcedureRequestService;
use App\Services\QueueService;
use App\Services\ServicePriceResolver;
use App\Services\VisitPathwayService;
use App\Services\VisitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationOrderWorkflowService
{
    public function __construct(
        private readonly ConsultationService $consultations,
        private readonly LabService $labs,
        private readonly ProcedureRequestService $procedures,
        private readonly ServicePriceResolver $priceResolver,
        private readonly VisitService $visits,
        private readonly ConsultationIdempotencyService $idempotency,
        private readonly QueueService $queues,
        private readonly VisitPathwayService $pathway,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function departmentServices(Department $department, ?Visit $visit = null)
    {
        $services = $department->services()
            ->with('prices')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price']);

        if (! $visit) {
            return $services;
        }

        return $services->map(function (ServiceCatalog $service) use ($visit) {
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

    public function departmentInvestigationInfo(Department $department): array
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

        return $data;
    }

    public function createInvestigation(Visit $visit, ConsultationActionContext $context, array $data): array
    {
        $created = [];
        $labRequest = null;

        if (! empty($data['service_ids'])) {
            $services = ServiceCatalog::whereIn('id', $data['service_ids'])->get();

            foreach ($services as $service) {
                $created[] = $this->consultations->addInvestigation($context->medicalRecord, [
                    'investigation_type' => $service->name,
                    'description' => $service->description ?? $service->name,
                    'urgency' => $data['urgency'] ?? 'routine',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $items = $services->map(fn (ServiceCatalog $service) => [
                'service_id' => $service->id,
                'name' => $service->name,
                'status' => 'pending',
            ])->all();

            if (! empty($items)) {
                $labRequest = $this->labs->createRequest($visit, $items, [
                    'target_department_id' => $data['department_id'] ?? null,
                    'clinical_info' => $data['notes'] ?? null,
                    'urgency' => $data['urgency'] ?? 'routine',
                    'medical_record_id' => $context->medicalRecord->id,
                    'consultation_route_id' => $context->route->id,
                ]);
            }
        } elseif (! empty($data['investigation_type'])) {
            $created[] = $this->consultations->addInvestigation($context->medicalRecord, [
                'investigation_type' => $data['investigation_type'],
                'description' => $data['description'] ?? $data['investigation_type'],
                'urgency' => $data['urgency'] ?? 'routine',
                'notes' => $data['notes'] ?? null,
            ]);

            $labRequest = $this->labs->createRequest($visit, [$data['investigation_type']], [
                'target_department_id' => $data['department_id'] ?? null,
                'clinical_info' => $data['description'] ?? ($data['notes'] ?? null),
                'urgency' => $data['urgency'] ?? 'routine',
                'medical_record_id' => $context->medicalRecord->id,
                'consultation_route_id' => $context->route->id,
            ]);
        }

        return [$created, $labRequest];
    }

    public function createProcedureRequest(Request $request, Visit $visit, ConsultationActionContext $context, array $data, User $user): ProcedureRequest
    {
        $payload = $data + [
            'visit_id' => $visit->id,
            'medical_record_id' => $context->medicalRecord->id,
            'consultation_route_id' => $context->route->id,
        ];

        return $this->idempotency->run(
            $request,
            'procedure.create',
            $visit,
            $context->route,
            $payload,
            fn () => $this->procedures->requestProcedure($payload, $user),
        );
    }

    public function createLabRequest(Request $request, Visit $visit, ConsultationActionContext $context, array $data): LabRequest
    {
        $payload = collect($data)->only('target_department_id', 'urgency', 'clinical_info')->all() + [
            'medical_record_id' => $context->medicalRecord->id,
            'consultation_route_id' => $context->route->id,
        ];

        return $this->idempotency->run(
            $request,
            'lab_request.create',
            $visit,
            $context->route,
            $payload + ['items' => $data['items'] ?? []],
            fn () => $this->labs->createRequest($visit, $data['items'] ?? [], $payload),
        );
    }

    public function sendToInvestigation(Visit $visit, int $departmentId, ?string $notes): void
    {
        $this->visits->sendToInvestigation($visit, $departmentId, $notes);
    }

    public function sendInvestigationRequestToDepartment(
        Visit $visit,
        ConsultationActionContext $context,
        LabRequest $labRequest,
        ?string $notes = null,
    ): LabRequest {
        $labRequest->loadMissing(['targetDepartment', 'department']);

        if ((int) $labRequest->visit_id !== (int) $visit->id) {
            throw new ConsultationActionException(__('messages.consultations.invalid_investigation_request'), 422, 'CONSULTATION_INVESTIGATION_REQUEST_INVALID');
        }

        if ($labRequest->consultation_route_id && (int) $labRequest->consultation_route_id !== (int) $context->route->id) {
            throw new ConsultationActionException(__('messages.consultations.investigation_request_wrong_session'), 422, 'CONSULTATION_INVESTIGATION_REQUEST_WRONG_SESSION');
        }

        if (in_array($labRequest->status, ['completed', 'cancelled'], true)) {
            throw new ConsultationActionException(__('messages.consultations.investigation_request_not_sendable'), 422, 'CONSULTATION_INVESTIGATION_REQUEST_CLOSED');
        }

        $departmentId = $labRequest->target_department_id ?: $labRequest->department_id;
        if (! $departmentId) {
            throw new ConsultationActionException(__('messages.consultations.investigation_request_department_missing'), 422, 'CONSULTATION_INVESTIGATION_DEPARTMENT_MISSING');
        }

        $department = Department::findOrFail($departmentId);
        $route = $context->route;
        $user = $context->user;
        $handoffNotes = $notes ?: __('messages.consultations.investigation_handoff_notes', [
            'number' => $labRequest->request_number,
            'department' => $department->name,
        ]);

        return DB::transaction(function () use ($visit, $route, $labRequest, $department, $departmentId, $user, $handoffNotes) {
            $this->handoffPatientToDepartment(
                visit: $visit,
                route: $route,
                user: $user,
                department: $department,
                targetStatus: VisitStatus::WAITING_INVESTIGATION,
                historyType: VisitDepartmentHistory::TYPE_INVESTIGATION,
                pathwayEvent: 'INVESTIGATION_DEPARTMENT_HANDOFF',
                activityEvent: 'CONSULTATION_INVESTIGATION_DEPARTMENT_HANDOFF',
                routeAction: 'sent_to_investigation_department',
                source: $labRequest,
                sourceMetadata: ['lab_request_id' => $labRequest->id, 'request_number' => $labRequest->request_number],
                notes: $handoffNotes,
            );

            return $labRequest->fresh(['targetDepartment', 'requestedBy', 'items.labTest', 'items.service', 'items.result']);
        });
    }

    public function sendInvestigationDepartmentToDepartment(
        Visit $visit,
        ConsultationActionContext $context,
        Department $department,
        ?string $notes = null,
    ): LabRequest {
        $labRequest = LabRequest::query()
            ->where('visit_id', $visit->id)
            ->where(function ($query) use ($department) {
                $query->where('target_department_id', $department->id)
                    ->orWhere(function ($fallback) use ($department) {
                        $fallback->whereNull('target_department_id')
                            ->where('department_id', $department->id);
                    });
            })
            ->where(function ($query) use ($context) {
                $query->whereNull('consultation_route_id')
                    ->orWhere('consultation_route_id', $context->route->id);
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->oldest()
            ->first();

        if (! $labRequest) {
            throw new ConsultationActionException(__('messages.consultations.investigation_department_no_open_request'), 422, 'CONSULTATION_INVESTIGATION_DEPARTMENT_NO_OPEN_REQUEST');
        }

        return $this->sendInvestigationRequestToDepartment($visit, $context, $labRequest, $notes);
    }

    public function sendPrescriptionDepartmentToDepartment(
        Visit $visit,
        ConsultationActionContext $context,
        Department $department,
        ?string $notes = null,
    ): Prescription {
        $department = $this->resolvePharmacyDepartment($department);

        $prescription = Prescription::query()
            ->where('visit_id', $visit->id)
            ->where(function ($query) use ($context) {
                $query->whereNull('consultation_route_id')
                    ->orWhere('consultation_route_id', $context->route->id);
            })
            ->whereNotIn('status', [
                PrescriptionStatus::DISPENSED->value,
                PrescriptionStatus::PARTIALLY_DISPENSED->value,
                PrescriptionStatus::CANCELLED->value,
            ])
            ->oldest()
            ->first();

        if (! $prescription) {
            throw new ConsultationActionException(__('messages.consultations.prescription_department_no_open_request'), 422, 'CONSULTATION_PRESCRIPTION_DEPARTMENT_NO_OPEN_REQUEST');
        }

        $handoffNotes = $notes ?: __('messages.consultations.prescription_handoff_notes', [
            'number' => $prescription->prescription_number,
            'department' => $department->name,
        ]);

        return DB::transaction(function () use ($visit, $context, $prescription, $department, $handoffNotes) {
            $this->handoffPatientToDepartment(
                visit: $visit,
                route: $context->route,
                user: $context->user,
                department: $department,
                targetStatus: VisitStatus::PHARMACY,
                historyType: VisitDepartmentHistory::TYPE_PHARMACY,
                pathwayEvent: 'PRESCRIPTION_DEPARTMENT_HANDOFF',
                activityEvent: 'CONSULTATION_PRESCRIPTION_DEPARTMENT_HANDOFF',
                routeAction: 'sent_to_pharmacy_department',
                source: $prescription,
                sourceMetadata: ['prescription_id' => $prescription->id, 'prescription_number' => $prescription->prescription_number],
                notes: $handoffNotes,
            );

            return $prescription->fresh(['items', 'creator', 'doctor', 'updater', 'sourcePattern']);
        });
    }

    public function sendProcedureDepartmentToDepartment(
        Visit $visit,
        ConsultationActionContext $context,
        Department $department,
        ?string $notes = null,
    ): ProcedureRequest {
        $procedureRequest = ProcedureRequest::query()
            ->where('visit_id', $visit->id)
            ->where('department_id', $department->id)
            ->where(function ($query) use ($context) {
                $query->whereNull('consultation_route_id')
                    ->orWhere('consultation_route_id', $context->route->id);
            })
            ->whereNotIn('status', array_map(fn (ProcedureStatus $status) => $status->value, ProcedureStatus::closedStatuses()))
            ->oldest()
            ->first();

        if (! $procedureRequest) {
            throw new ConsultationActionException(__('messages.consultations.procedure_department_no_open_request'), 422, 'CONSULTATION_PROCEDURE_DEPARTMENT_NO_OPEN_REQUEST');
        }

        $handoffNotes = $notes ?: __('messages.consultations.procedure_handoff_notes', [
            'number' => $procedureRequest->request_number,
            'department' => $department->name,
        ]);

        return DB::transaction(function () use ($visit, $context, $procedureRequest, $department, $handoffNotes) {
            $this->handoffPatientToDepartment(
                visit: $visit,
                route: $context->route,
                user: $context->user,
                department: $department,
                targetStatus: VisitStatus::ACTIVE,
                historyType: VisitDepartmentHistory::TYPE_PROCEDURE,
                pathwayEvent: 'PROCEDURE_DEPARTMENT_HANDOFF',
                activityEvent: 'CONSULTATION_PROCEDURE_DEPARTMENT_HANDOFF',
                routeAction: 'sent_to_procedure_department',
                source: $procedureRequest,
                sourceMetadata: ['procedure_request_id' => $procedureRequest->id, 'request_number' => $procedureRequest->request_number],
                notes: $handoffNotes,
            );

            return $procedureRequest->fresh(['service', 'department', 'requestingDoctor', 'schedule.theatreRoom']);
        });
    }

    private function handoffPatientToDepartment(
        Visit $visit,
        VisitConsultationRoute $route,
        User $user,
        Department $department,
        VisitStatus $targetStatus,
        string $historyType,
        string $pathwayEvent,
        string $activityEvent,
        string $routeAction,
        Model $source,
        array $sourceMetadata,
        string $notes,
    ): void {
        $departmentId = (int) $department->id;
        $visit = $visit->fresh(['queueEntries']);
        $route = $route->fresh();
        $previousRouteStatus = $route->status;

        if ($route->status === VisitConsultationRoute::STATUS_ACTIVE) {
            $route->forceFill([
                'status' => VisitConsultationRoute::STATUS_PAUSED,
                'paused_at' => now(),
            ])->save();

            VisitConsultationRouteLog::create([
                'visit_consultation_route_id' => $route->id,
                'visit_id' => $visit->id,
                'from_status' => $previousRouteStatus,
                'to_status' => VisitConsultationRoute::STATUS_PAUSED,
                'action' => $routeAction,
                'notes' => $notes,
                'performed_by' => $user->id,
            ]);
        }

        $this->completeCurrentQueueBeforeHandoff($visit, $departmentId);

        $fromStatus = $visit->status;
        $visit->forceFill([
            'status' => $targetStatus,
            'current_department_id' => $departmentId,
            'checked_out_at' => null,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();

        if ($fromStatus !== $targetStatus) {
            $visit->statusLogs()->create([
                'from_status' => $fromStatus?->value,
                'to_status' => $targetStatus->value,
                'changed_by' => $user->id,
                'notes' => $notes,
            ]);
        }

        VisitDepartmentHistory::create([
            'visit_id' => $visit->id,
            'department_id' => $departmentId,
            'type' => $historyType,
            'status' => VisitDepartmentHistory::STATUS_WAITING,
            'assigned_by' => $user->id,
            'notes' => $notes,
        ]);

        $this->queues->ensureForDepartment($visit->fresh(), $departmentId);

        $this->pathway->record($visit->fresh(), $pathwayEvent, [
            'source' => $source,
            'department_id' => $departmentId,
            'title' => "Patient sent to {$department->name}",
            'description' => $notes,
        ]);

        $this->activityLog->log(
            LogModule::CONSULTATION,
            $activityEvent,
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'consultation_route_id' => $route->id,
                'department_id' => $departmentId,
                'causer' => $user,
                'metadata' => array_merge($sourceMetadata, [
                    'previous_route_status' => $previousRouteStatus,
                    'new_route_status' => $route->fresh()->status,
                    'previous_visit_status' => $fromStatus?->value,
                    'new_visit_status' => $targetStatus->value,
                ]),
            ],
            $source,
            'Patient sent to department from consultation.',
        );
    }

    private function resolvePharmacyDepartment(Department $department): Department
    {
        $type = $department->type instanceof \App\Enums\DepartmentType ? $department->type->value : (string) $department->type;
        if ($type === \App\Enums\DepartmentType::PHARMACY->value) {
            return $department;
        }

        $pharmacy = Department::query()
            ->where('type', \App\Enums\DepartmentType::PHARMACY->value)
            ->where('status', 'active')
            ->orderBy('name')
            ->first();

        if (! $pharmacy) {
            throw new ConsultationActionException(__('messages.consultations.pharmacy_department_missing'), 422, 'CONSULTATION_PHARMACY_DEPARTMENT_MISSING');
        }

        return $pharmacy;
    }

    private function completeCurrentQueueBeforeHandoff(Visit $visit, int $targetDepartmentId): void
    {
        $entry = $visit->queueEntries()
            ->whereIn('status', ['waiting', 'serving'])
            ->where(function ($query) use ($targetDepartmentId) {
                $query->whereNull('department_id')
                    ->orWhere('department_id', '!=', $targetDepartmentId);
            })
            ->latest()
            ->first();

        if ($entry) {
            $this->queues->markCompleted($entry);
        }
    }
}
