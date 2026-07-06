<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\InvoiceItem;
use App\Models\ServiceCatalog;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Collection;

class ConsultationSpecialtyBillingMappingService
{
    public function resolveDefaultConsultationService(
        ConsultationSpecialtyProfile $profile,
        mixed $department = null,
        mixed $consultationRoute = null,
        array $options = [],
    ): ?ConsultationSpecialtyServiceMapping {
        return $this->resolveMapping(
            $profile,
            $options['context'] ?? ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
            $department,
            $consultationRoute,
        );
    }

    public function resolveMappingsForContext(
        ConsultationSpecialtyProfile $profile,
        string $context,
        mixed $department = null,
        mixed $consultationRoute = null
    ): Collection {
        $routeId = $this->idFrom($consultationRoute);
        $routeDepartment = $this->routeDepartment($consultationRoute);
        $departmentId = $this->idFrom($department) ?: $this->idFrom($routeDepartment);
        $departmentType = $this->departmentTypeValue($this->objectFrom($department) ?: $routeDepartment);

        return ConsultationSpecialtyServiceMapping::query()
            ->with(['service', 'department'])
            ->forProfile($profile)
            ->forContext($context)
            ->active()
            ->whereHas('service', fn ($query) => $query->where('is_active', true))
            ->where(function ($query) use ($routeId, $departmentId, $departmentType) {
                $query->where(function ($routeQuery) use ($routeId) {
                    $routeQuery->whereNotNull('consultation_route_id')->where('consultation_route_id', $routeId);
                })->orWhere(function ($departmentQuery) use ($departmentId) {
                    $departmentQuery->whereNotNull('department_id')->where('department_id', $departmentId);
                })->orWhere(function ($typeQuery) use ($departmentType) {
                    $typeQuery->whereNotNull('department_type')->where('department_type', $departmentType);
                })->orWhere(function ($defaultQuery) {
                    $defaultQuery->where('is_default', true)
                        ->whereNull('consultation_route_id')
                        ->whereNull('department_id')
                        ->whereNull('department_type');
                });
            })
            ->ordered()
            ->get();
    }

    public function resolveMapping(
        ConsultationSpecialtyProfile $profile,
        string $context,
        mixed $department = null,
        mixed $consultationRoute = null,
    ): ?ConsultationSpecialtyServiceMapping {
        $routeId = $this->idFrom($consultationRoute);
        $routeDepartment = $this->routeDepartment($consultationRoute);
        $departmentId = $this->idFrom($department) ?: $this->idFrom($routeDepartment);
        $departmentType = $this->departmentTypeValue($this->objectFrom($department) ?: $routeDepartment);

        return $this->baseQuery($profile, $context)
            ->where('consultation_route_id', $routeId)
            ->whereNotNull('consultation_route_id')
            ->ordered()
            ->first()
            ?? $this->baseQuery($profile, $context)
                ->where('department_id', $departmentId)
                ->whereNotNull('department_id')
                ->ordered()
                ->first()
            ?? $this->baseQuery($profile, $context)
                ->where('department_type', $departmentType)
                ->whereNotNull('department_type')
                ->ordered()
                ->first()
            ?? $this->baseQuery($profile, $context)
                ->defaults()
                ->whereNull('consultation_route_id')
                ->whereNull('department_id')
                ->whereNull('department_type')
                ->ordered()
                ->first();
    }

    public function getWorkspaceBillingContext(
        $consultation,
        ResolvedConsultationSpecialty|array $specialtyContext,
        array $workspacePayload = []
    ): array {
        try {
            $profile = $specialtyContext instanceof ResolvedConsultationSpecialty
                ? $specialtyContext->profile
                : ConsultationSpecialtyProfile::query()->find(data_get($specialtyContext, 'profile.id'));

            if (! $profile instanceof ConsultationSpecialtyProfile) {
                return $this->emptyContext();
            }

            $route = $consultation instanceof VisitConsultationRoute ? $consultation : null;
            $department = $route?->department ?? ($specialtyContext instanceof ResolvedConsultationSpecialty ? $specialtyContext->department : null);
            $mapping = $this->resolveDefaultConsultationService($profile, $department, $route);
            $suggestions = $this->suggestBillableServices($profile, ConsultationSpecialtyServiceMapping::CONTEXT_PROCEDURE, [
                'department' => $department,
                'consultation_route' => $route,
            ]);

            $warnings = [];
            if (! $mapping) {
                $warnings[] = __('consultation_specialties.billing.warning_no_mapping');
            }

            return [
                'default_service' => $mapping ? $this->mappingPayload($mapping, $route) : null,
                'billable_suggestions' => $suggestions,
                'auto_bill_enabled' => (bool) ($mapping?->auto_bill ?? false),
                'requires_confirmation' => (bool) ($mapping?->requires_confirmation ?? true),
                'already_billed' => $mapping ? $this->alreadyBilled($route, $mapping) : false,
                'warnings' => $warnings,
                'preview_url' => $mapping && $route ? route('admin.consultations.specialty-billing.preview', [$route->visit_id, 'consultation_route_id' => $route->id]) : null,
                'apply_url' => $mapping && $route ? route('admin.consultations.specialty-billing.apply', [$route->visit_id, 'consultation_route_id' => $route->id]) : null,
            ];
        } catch (\Throwable) {
            return $this->emptyContext();
        }
    }

    public function canAutoBill($consultation, ConsultationSpecialtyServiceMapping $mapping, $user, array $options = []): array
    {
        $warnings = [];
        if (! $mapping->is_active) {
            $warnings[] = __('consultation_specialties.billing.inactive_mapping');
        }
        if (! $mapping->service?->is_active) {
            $warnings[] = __('consultation_specialties.billing.inactive_service');
        }
        if (! $mapping->auto_bill) {
            $warnings[] = __('consultation_specialties.billing.auto_bill_disabled');
        }
        if ($mapping->requires_confirmation && empty($options['confirmed'])) {
            $warnings[] = __('consultation_specialties.billing.requires_confirmation');
        }
        if ($this->alreadyBilled($consultation, $mapping)) {
            $warnings[] = __('consultation_specialties.billing.already_billed');
        }
        if (! $user?->can('invoices.create')) {
            $warnings[] = __('consultation_specialties.billing.not_authorized');
        }

        return ['can_bill' => $warnings === [], 'warnings' => $warnings];
    }

    public function suggestBillableServices(ConsultationSpecialtyProfile $profile, string $context, array $options = []): array
    {
        return $this->resolveMappingsForContext($profile, $context, $options['department'] ?? null, $options['consultation_route'] ?? null)
            ->map(fn (ConsultationSpecialtyServiceMapping $mapping) => $this->mappingPayload($mapping, $options['consultation_route'] ?? null))
            ->values()
            ->all();
    }

    public function alreadyBilled($consultation, ConsultationSpecialtyServiceMapping $mapping): bool
    {
        $route = $consultation instanceof VisitConsultationRoute ? $consultation : null;
        if (! $route || ! $mapping->service_id) {
            return false;
        }

        return InvoiceItem::query()
            ->where('visit_id', $route->visit_id)
            ->where('service_catalog_id', $mapping->service_id)
            ->where(function ($query) use ($mapping) {
                $query->whereIn('source_type', [
                    InvoiceItem::SOURCE_CONSULTATION_SERVICE,
                    InvoiceItem::SOURCE_SPECIALTY_SERVICE_MAPPING,
                    'service_catalog',
                    'visit_service',
                    'visit_selected_service',
                    'visit_creation',
                ])
                    ->orWhere('source_id', $mapping->id);
            })
            ->exists();
    }

    public function mappingPayload(ConsultationSpecialtyServiceMapping $mapping, mixed $consultation = null): array
    {
        $service = $mapping->service;

        return [
            'mapping_id' => $mapping->id,
            'context' => $mapping->mapping_context,
            'trigger' => $mapping->billing_trigger,
            'auto_bill' => (bool) $mapping->auto_bill,
            'requires_confirmation' => (bool) $mapping->requires_confirmation,
            'already_billed' => $this->alreadyBilled($consultation, $mapping),
            'service' => $service ? [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
                'price' => (float) $service->price,
                'is_active' => (bool) $service->is_active,
            ] : null,
        ];
    }

    private function baseQuery(ConsultationSpecialtyProfile $profile, string $context)
    {
        return ConsultationSpecialtyServiceMapping::query()
            ->with(['service', 'department'])
            ->forProfile($profile)
            ->forContext($context)
            ->active()
            ->whereHas('service', fn ($query) => $query->where('is_active', true));
    }

    private function emptyContext(): array
    {
        return [
            'default_service' => null,
            'billable_suggestions' => [],
            'auto_bill_enabled' => false,
            'requires_confirmation' => true,
            'already_billed' => false,
            'warnings' => [],
            'preview_url' => null,
            'apply_url' => null,
        ];
    }

    private function idFrom(mixed $value): ?int
    {
        return is_object($value) ? ($value->id ?? null) : (is_numeric($value) ? (int) $value : null);
    }

    private function objectFrom(mixed $value): ?object
    {
        return is_object($value) ? $value : null;
    }

    private function routeDepartment(mixed $consultationRoute): ?object
    {
        return is_object($consultationRoute) ? ($consultationRoute->department ?? null) : null;
    }

    private function departmentTypeValue(?object $department): ?string
    {
        $type = $department?->type ?? null;

        return $type instanceof \BackedEnum ? $type->value : $type;
    }
}
