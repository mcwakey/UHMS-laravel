<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\InvestigationCriterion;
use App\Models\InvestigationHeader;
use App\Models\ServiceCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class InvestigationCatalogueService
{
    /**
     * Investigation-type departments contributing services to the catalogue.
     */
    public function investigationDepartmentTypes(): array
    {
        return [
            DepartmentType::INVESTIGATION->value,
            DepartmentType::RADIOLOGY->value,
        ];
    }

    /**
     * Paginated list of services in the investigation catalogue.
     */
    public function listServices(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ServiceCatalog::query()
            ->with(['department'])
            ->whereHas('department', function ($q) {
                $q->whereIn('type', $this->investigationDepartmentTypes());
            })
            ->withCount(['investigationHeaders', 'investigationCriteria'])
            ->orderBy('name');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['active'])) {
            $query->where('is_active', (bool) $filters['active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Configuration payload for a single service.
     */
    public function getServiceConfig(ServiceCatalog $service): array
    {
        $service->load([
            'department',
            'investigationHeaders.criteria' => fn ($q) => $q->orderBy('sort_order'),
            'investigationCriteria' => fn ($q) => $q->whereNull('header_id')->orderBy('sort_order'),
        ]);

        $serviceConsumables = $service->consumables()->with('product')->get();
        $availableProducts  = app(\App\Services\ServiceConsumableService::class)->availableProductsFor($service);

        return [
            'service'             => $service,
            'headers'             => $service->investigationHeaders,
            'unsorted_criteria'   => $service->investigationCriteria,
            'serviceConsumables'  => $serviceConsumables,
            'availableProducts'   => $availableProducts,
        ];
    }

    /**
     * Persist the per-service overall result type configuration.
     *
     * Only fields relevant to the chosen type are kept; the rest are nulled so
     * stale unit/range/labels don't leak across type changes.
     */
    public function updateOverallResultConfig(ServiceCatalog $service, array $data): ServiceCatalog
    {
        $type = $data['overall_result_type'] ?? ServiceCatalog::OVERALL_RESULT_FREE_TEXT;

        $payload = [
            'overall_result_type'           => $type,
            'overall_result_unit'           => null,
            'overall_result_min_value'      => null,
            'overall_result_max_value'      => null,
            'overall_result_positive_label' => null,
            'overall_result_negative_label' => null,
            'overall_result_true_label'     => null,
            'overall_result_false_label'    => null,
        ];

        if ($type === ServiceCatalog::OVERALL_RESULT_NUMERIC) {
            $payload['overall_result_unit']      = $data['overall_result_unit'] ?? null;
            $payload['overall_result_min_value'] = $data['overall_result_min_value'] ?? null;
            $payload['overall_result_max_value'] = $data['overall_result_max_value'] ?? null;
        } elseif ($type === ServiceCatalog::OVERALL_RESULT_POSITIVE_NEGATIVE) {
            $payload['overall_result_positive_label'] = $data['overall_result_positive_label'] ?? null;
            $payload['overall_result_negative_label'] = $data['overall_result_negative_label'] ?? null;
        } elseif ($type === ServiceCatalog::OVERALL_RESULT_BOOLEAN) {
            $payload['overall_result_true_label']  = $data['overall_result_true_label'] ?? null;
            $payload['overall_result_false_label'] = $data['overall_result_false_label'] ?? null;
        }

        $service->update($payload);

        return $service->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    */

    public function addHeader(ServiceCatalog $service, array $data): InvestigationHeader
    {
        return InvestigationHeader::create([
            'service_id'  => $service->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order'  => $data['sort_order'] ?? $this->nextHeaderSort($service),
            'is_active'   => $data['is_active'] ?? true,
        ]);
    }

    public function updateHeader(InvestigationHeader $header, array $data): InvestigationHeader
    {
        $payload = [];

        foreach (['name', 'description', 'sort_order', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $header->update($payload);

        return $header->fresh();
    }

    public function deleteHeader(InvestigationHeader $header): bool
    {
        // Detach criteria from this header (don't delete the criteria themselves)
        InvestigationCriterion::where('header_id', $header->id)->update(['header_id' => null]);
        return (bool) $header->delete();
    }

    private function nextHeaderSort(ServiceCatalog $service): int
    {
        return (int) (InvestigationHeader::where('service_id', $service->id)->max('sort_order') ?? 0) + 10;
    }

    /*
    |--------------------------------------------------------------------------
    | Criteria
    |--------------------------------------------------------------------------
    */

    public function addCriterion(ServiceCatalog $service, array $data): InvestigationCriterion
    {
        return InvestigationCriterion::create([
            'service_id'      => $service->id,
            'header_id'       => $data['header_id'] ?? null,
            'name'            => $data['name'],
            'unit'            => $data['unit'] ?? null,
            'reference_range' => $data['reference_range'] ?? null,
            'default_value'   => $data['default_value'] ?? null,
            'input_type'      => $data['input_type'] ?? 'text',
            'options'         => $data['options'] ?? null,
            'sort_order'      => $data['sort_order'] ?? $this->nextCriterionSort($service, $data['header_id'] ?? null),
            'is_required'     => $data['is_required'] ?? false,
            'is_active'       => $data['is_active'] ?? true,
        ]);
    }

    public function updateCriterion(InvestigationCriterion $criterion, array $data): InvestigationCriterion
    {
        $payload = [];

        foreach ([
            'header_id',
            'name',
            'unit',
            'reference_range',
            'default_value',
            'input_type',
            'options',
            'sort_order',
            'is_required',
            'is_active',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $criterion->update($payload);

        return $criterion->fresh();
    }

    public function deleteCriterion(InvestigationCriterion $criterion): bool
    {
        return (bool) $criterion->delete();
    }

    private function nextCriterionSort(ServiceCatalog $service, ?int $headerId): int
    {
        return (int) (InvestigationCriterion::where('service_id', $service->id)
            ->where('header_id', $headerId)
            ->max('sort_order') ?? 0) + 10;
    }

    /**
     * Return effective criteria configuration for a service (used by result entry).
     * Groups active criteria by their header (or "__no_header__").
     */
    public function effectiveCriteria(ServiceCatalog $service): Collection
    {
        return InvestigationCriterion::where('service_id', $service->id)
            ->where('is_active', true)
            ->orderBy('header_id')
            ->orderBy('sort_order')
            ->get();
    }
}
