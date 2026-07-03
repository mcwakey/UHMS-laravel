<?php

namespace App\Services\Consultation;

use App\Enums\ResultType;
use App\Models\Department;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\ProcedureRequest;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Services\ConsultationService;
use App\Services\LabService;
use App\Services\ProcedureRequestService;
use App\Services\ServicePriceResolver;
use App\Services\VisitService;
use Illuminate\Http\Request;

class ConsultationOrderWorkflowService
{
    public function __construct(
        private readonly ConsultationService $consultations,
        private readonly LabService $labs,
        private readonly ProcedureRequestService $procedures,
        private readonly ServicePriceResolver $priceResolver,
        private readonly VisitService $visits,
        private readonly ConsultationIdempotencyService $idempotency,
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
}

