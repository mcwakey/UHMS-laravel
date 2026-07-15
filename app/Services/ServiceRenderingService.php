<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\InvoiceItem;
use App\Models\ServiceRendering;
use App\Models\ServiceRenderingLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceRenderingService
{
    private const EXCLUDED_SOURCE_TYPES = [
        InvoiceItem::SOURCE_CONSULTATION_SERVICE,
        InvoiceItem::SOURCE_INVESTIGATION_SERVICE,
        InvoiceItem::SOURCE_PROCEDURE_SERVICE,
        InvoiceItem::SOURCE_PHARMACY_PRODUCT,
        InvoiceItem::SOURCE_PHARMACY_BILLING_SELECTION,
        InvoiceItem::SOURCE_WARD_CONSUMABLE,
        InvoiceItem::SOURCE_EMERGENCY_CONSUMABLE,
        InvoiceItem::SOURCE_EMERGENCY_BED_CHARGE,
        InvoiceItem::SOURCE_EMERGENCY_DAILY_CONSUMABLE_CHARGE,
        InvoiceItem::SOURCE_ADMISSION_FEE,
        InvoiceItem::SOURCE_ADMISSION_BED_CHARGE,
        InvoiceItem::SOURCE_ADMISSION_DAILY_CONSUMABLE_CHARGE,
        InvoiceItem::SOURCE_INVESTIGATION_CONSUMABLE,
        InvoiceItem::SOURCE_PROCEDURE_CONSUMABLE,
    ];

    private const EXCLUDED_CATEGORIES = [
        'consultation',
        'investigation',
        'lab',
        'laboratory',
        'radiology',
        'xray',
        'x-ray',
        'scan',
        'pharmacy',
        'drug',
        'drugs',
        'medication',
        'product',
        'procedure',
        'procedures',
        'theatre',
        'surgery',
        'bed_charge',
        'admission_fee',
        'registration',
        'administrative',
        'consumable',
        'consumables',
    ];

    // Department types tracked by a dedicated workflow (consultation routes, lab
    // requests, theatre/procedure, dispensing, emergency sessions) — their invoice
    // items must NOT also create a generic service-rendering entry. EMERGENCY and
    // THEATRE are explicit here so the department-type split preserves the prior
    // behaviour (they were previously excluded as consultation / procedure).
    private const EXCLUDED_DEPARTMENT_TYPES = [
        DepartmentType::CONSULTATION->value,
        DepartmentType::EMERGENCY->value,
        DepartmentType::INVESTIGATION->value,
        DepartmentType::RADIOLOGY->value,
        DepartmentType::PROCEDURE->value,
        DepartmentType::THEATRE->value,
        DepartmentType::PHARMACY->value,
        DepartmentType::ADMINISTRATIVE->value,
    ];

    public function __construct(
        private VisitPathwayService $pathway,
        private ActivityLogService $logger,
        private NotificationService $notifications,
    ) {}

    public function shouldTrackInvoiceItem(InvoiceItem $item): bool
    {
        $item->loadMissing('serviceCatalog.department');
        $service = $item->serviceCatalog;

        if (! $service) {
            return false;
        }

        // Explicit flag takes priority over all heuristics.
        if (array_key_exists('requires_rendering_tracking', $service->getAttributes())) {
            if ($service->requires_rendering_tracking === true) {
                return true;
            }
            if ($service->requires_rendering_tracking === false) {
                return false;
            }
        }

        $sourceType = strtolower((string) $item->source_type);
        if (in_array($item->source_type, self::EXCLUDED_SOURCE_TYPES, true)) {
            return false;
        }

        foreach (['consultation', 'investigation', 'lab', 'radiology', 'pharmacy', 'drug', 'product', 'procedure', 'theatre', 'surgery', 'consumable', 'bed_charge'] as $specializedNeedle) {
            if ($sourceType !== '' && str_contains($sourceType, $specializedNeedle)) {
                return false;
            }
        }

        $category = strtolower((string) $service->category);
        if (in_array($category, self::EXCLUDED_CATEGORIES, true)) {
            return false;
        }

        $departmentType = $this->normaliseDepartmentType($service->department_type ?? $service->department?->type);
        if (in_array($departmentType, self::EXCLUDED_DEPARTMENT_TYPES, true)) {
            return false;
        }

        return true;
    }

    public function createForInvoiceItem(InvoiceItem $item, ?User $user = null): ?ServiceRendering
    {
        $item->loadMissing([
            'invoice',
            'visit.emergencyCase',
            'visit.admission',
            'visit.activeConsultationRoute',
            'patient',
            'department',
            'serviceCatalog.department',
        ]);

        if (! $this->shouldTrackInvoiceItem($item)) {
            return null;
        }

        $visit = $item->visit ?: $item->invoice?->visit;
        $patientId = $item->patient_id ?: $item->invoice?->patient_id ?: $visit?->patient_id;
        $service = $item->serviceCatalog;

        if (! $visit || ! $patientId || ! $service) {
            return null;
        }

        return DB::transaction(function () use ($item, $user, $visit, $patientId, $service) {
            $actorId = $user?->id ?? Auth::id() ?? $item->created_by;
            $departmentId = $item->department_id ?: $service->department_id;
            $emergencyCase = $visit->emergencyCase;
            $admission = $visit->admission;
            $consultationRoute = $visit->activeConsultationRoute;

            $rendering = ServiceRendering::query()->firstOrCreate(
                ['invoice_item_id' => $item->id],
                [
                    'visit_id' => $visit->id,
                    'patient_id' => $patientId,
                    'service_id' => $service->id,
                    'department_id' => $departmentId,
                    'emergency_case_id' => $emergencyCase?->id,
                    'admission_id' => $admission?->id,
                    'consultation_route_id' => $consultationRoute?->id,
                    'status' => ServiceRendering::STATUS_PENDING,
                    'created_by' => $actorId,
                ],
            );

            if (! $rendering->wasRecentlyCreated) {
                return $rendering;
            }

            $this->recordAction(
                $rendering,
                'CREATED',
                null,
                ServiceRendering::STATUS_PENDING,
                $actorId,
                'Rendering required for billed service.',
            );

            $this->notifyDepartment($rendering);

            return $rendering;
        });
    }

    public function start(ServiceRendering $rendering, User $user, ?string $notes = null): ServiceRendering
    {
        $this->assertCan($user, $rendering, 'service_rendering.start');

        if (! in_array($rendering->status, [ServiceRendering::STATUS_PENDING, ServiceRendering::STATUS_ON_HOLD], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or on-hold services can be started.',
            ]);
        }

        return DB::transaction(function () use ($rendering, $user, $notes) {
            $from = $rendering->status;
            $rendering->forceFill([
                'status' => ServiceRendering::STATUS_IN_PROGRESS,
                'started_by' => $rendering->started_by ?: $user->id,
                'started_at' => $rendering->started_at ?: now(),
                'notes' => $notes ?? $rendering->notes,
                'updated_by' => $user->id,
            ])->save();

            $this->recordAction($rendering, 'STARTED', $from, ServiceRendering::STATUS_IN_PROGRESS, $user->id, $notes);

            return $rendering->fresh($this->defaultRelations());
        });
    }

    public function markRendered(ServiceRendering $rendering, User $user, array $data): ServiceRendering
    {
        $this->assertCan($user, $rendering, 'service_rendering.mark_rendered');
        $this->assertClosable($rendering, 'Only pending, in-progress, or on-hold services can be marked rendered.');

        return DB::transaction(function () use ($rendering, $user, $data) {
            $from = $rendering->status;
            $rendering->forceFill([
                'status' => ServiceRendering::STATUS_RENDERED,
                'rendered_by' => $user->id,
                'rendered_at' => $data['rendered_at'] ?? now(),
                'result_summary' => $data['result_summary'] ?? $rendering->result_summary,
                'notes' => $data['notes'] ?? $rendering->notes,
                'reason_not_rendered' => null,
                'updated_by' => $user->id,
            ])->save();

            $this->recordAction(
                $rendering,
                'RENDERED',
                $from,
                ServiceRendering::STATUS_RENDERED,
                $user->id,
                $data['notes'] ?? $data['result_summary'] ?? null,
            );

            return $rendering->fresh($this->defaultRelations());
        });
    }

    public function markNotRendered(ServiceRendering $rendering, User $user, array $data): ServiceRendering
    {
        $this->assertCan($user, $rendering, 'service_rendering.mark_not_rendered');
        $this->assertClosable($rendering, 'Only pending, in-progress, or on-hold services can be marked not rendered.');

        return DB::transaction(function () use ($rendering, $user, $data) {
            $from = $rendering->status;
            $rendering->forceFill([
                'status' => ServiceRendering::STATUS_NOT_RENDERED,
                'reason_not_rendered' => $data['reason_not_rendered'],
                'notes' => $data['notes'] ?? $rendering->notes,
                'updated_by' => $user->id,
            ])->save();

            $this->recordAction(
                $rendering,
                'NOT_RENDERED',
                $from,
                ServiceRendering::STATUS_NOT_RENDERED,
                $user->id,
                $data['notes'] ?? null,
                $data['reason_not_rendered'],
            );

            return $rendering->fresh($this->defaultRelations());
        });
    }

    public function cancel(ServiceRendering $rendering, User $user, array $data): ServiceRendering
    {
        $this->assertCan($user, $rendering, 'service_rendering.cancel');

        if ($rendering->status === ServiceRendering::STATUS_RENDERED && ! $user->can('service_rendering.correct_completed')) {
            throw new AuthorizationException('Rendered services require correction permission before cancellation.');
        }

        return DB::transaction(function () use ($rendering, $user, $data) {
            $from = $rendering->status;
            $rendering->forceFill([
                'status' => ServiceRendering::STATUS_CANCELLED,
                'reason_not_rendered' => $data['reason'] ?? $rendering->reason_not_rendered,
                'notes' => $data['notes'] ?? $rendering->notes,
                'updated_by' => $user->id,
            ])->save();

            $this->recordAction(
                $rendering,
                'CANCELLED',
                $from,
                ServiceRendering::STATUS_CANCELLED,
                $user->id,
                $data['notes'] ?? null,
                $data['reason'] ?? null,
            );

            return $rendering->fresh($this->defaultRelations());
        });
    }

    public function updateNotes(ServiceRendering $rendering, User $user, array $data): ServiceRendering
    {
        $this->assertCan($user, $rendering, 'service_rendering.edit_notes');

        if ($rendering->status === ServiceRendering::STATUS_RENDERED && ! $user->can('service_rendering.correct_completed')) {
            throw new AuthorizationException('Completed service notes require correction permission.');
        }

        return DB::transaction(function () use ($rendering, $user, $data) {
            $from = $rendering->status;
            $rendering->forceFill([
                'notes' => $data['notes'] ?? $rendering->notes,
                'result_summary' => $data['result_summary'] ?? $rendering->result_summary,
                'updated_by' => $user->id,
            ])->save();

            $this->recordAction($rendering, 'NOTES_UPDATED', $from, $rendering->status, $user->id, $data['notes'] ?? null);

            return $rendering->fresh($this->defaultRelations());
        });
    }

    public function assertCan(User $user, ServiceRendering $rendering, string $permission): void
    {
        if (! $user->can($permission)) {
            throw new AuthorizationException('You are not authorized to manage this service rendering.');
        }

        if ($user->can('service_rendering.view_all')) {
            return;
        }

        if ($rendering->department_id && (int) $rendering->department_id !== (int) $user->department_id) {
            throw new AuthorizationException('This service rendering belongs to another department.');
        }
    }

    public function defaultRelations(): array
    {
        return [
            'patient',
            'visit',
            'invoiceItem.invoice',
            'service',
            'department',
            'emergencyCase',
            'admission',
            'consultationRoute',
            'renderedBy',
            'startedBy',
            'createdBy',
            'updatedBy',
            'logs.performedBy',
        ];
    }

    private function assertClosable(ServiceRendering $rendering, string $message): void
    {
        if (! in_array($rendering->status, ServiceRendering::ACTIVE_STATUSES, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function recordAction(
        ServiceRendering $rendering,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $actorId,
        ?string $notes = null,
        ?string $reason = null,
    ): void {
        $rendering->loadMissing(['visit', 'patient', 'service', 'department']);

        ServiceRenderingLog::create([
            'service_rendering_id' => $rendering->id,
            'visit_id' => $rendering->visit_id,
            'patient_id' => $rendering->patient_id,
            'service_id' => $rendering->service_id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $action,
            'notes' => $notes,
            'reason' => $reason,
            'performed_by' => $actorId,
        ]);

        $description = $notes ?: $reason ?: 'Service rendering action recorded.';

        $this->logger->log(
            LogModule::SERVICE_RENDERING,
            $action,
            [
                'description' => $description,
                'patient_id' => $rendering->patient_id,
                'visit_id' => $rendering->visit_id,
                'department_id' => $rendering->department_id,
                'metadata' => [
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'service_id' => $rendering->service_id,
                    'invoice_item_id' => $rendering->invoice_item_id,
                ],
            ],
            $rendering,
            $description,
        );

        if ($rendering->visit) {
            $this->pathway->record($rendering->visit, 'SERVICE_RENDERING_'.$action, [
                'patient_id' => $rendering->patient_id,
                'department_id' => $rendering->department_id,
                'source' => $rendering,
                'status' => $toStatus,
                'title' => $this->pathwayTitle($action, $rendering),
                'description' => $description,
                'started_at' => match ($action) {
                    'STARTED' => $rendering->started_at ?? now(),
                    'RENDERED' => $rendering->rendered_at ?? now(),
                    default => now(),
                },
                'completed_at' => in_array($toStatus, [
                    ServiceRendering::STATUS_RENDERED,
                    ServiceRendering::STATUS_NOT_RENDERED,
                    ServiceRendering::STATUS_CANCELLED,
                ], true) ? now() : null,
                'created_by' => $actorId,
            ]);
        }
    }

    private function notifyDepartment(ServiceRendering $rendering): void
    {
        try {
            $this->notifications->notifyDepartment($rendering->department_id, [
                'module' => NotificationModule::SERVICE_RENDERING,
                'priority' => NotificationPriority::NORMAL,
                'title' => 'Service rendering required',
                'message' => ($rendering->service?->name ?? 'A billed service').' is waiting for fulfilment.',
                'action_url' => app(\App\Services\WorkspaceRouteResolver::class)->route('admin.service-renderings.show', $rendering),
                'source_type' => ServiceRendering::class,
                'source_id' => $rendering->id,
            ]);
        } catch (\Throwable) {
            // Notifications must not block billing or rendering creation.
        }
    }

    private function pathwayTitle(string $action, ServiceRendering $rendering): string
    {
        $service = $rendering->service?->name ?? 'Service';

        return match ($action) {
            'CREATED' => "{$service} awaiting rendering",
            'STARTED' => "{$service} rendering started",
            'RENDERED' => "{$service} rendered",
            'NOT_RENDERED' => "{$service} not rendered",
            'CANCELLED' => "{$service} rendering cancelled",
            'NOTES_UPDATED' => "{$service} rendering notes updated",
            default => "{$service} rendering updated",
        };
    }

    private function normaliseDepartmentType(mixed $type): string
    {
        if ($type instanceof DepartmentType) {
            return $type->value;
        }

        if (is_object($type) && property_exists($type, 'value')) {
            return (string) $type->value;
        }

        return strtolower((string) $type);
    }
}
