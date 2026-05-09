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

        return [
            'service'           => $service,
            'headers'           => $service->investigationHeaders,
            'unsorted_criteria' => $service->investigationCriteria, // criteria without a header
        ];
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
        $header->update(array_filter([
            'name'        => $data['name']        ?? null,
            'description' => $data['description'] ?? null,
            'sort_order'  => $data['sort_order']  ?? null,
            'is_active'   => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
        ], fn ($v) => !is_null($v)));

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
        $criterion->update(array_filter([
            'header_id'       => array_key_exists('header_id', $data) ? $data['header_id'] : '__skip__',
            'name'            => $data['name']            ?? null,
            'unit'            => $data['unit']            ?? null,
            'reference_range' => $data['reference_range'] ?? null,
            'default_value'   => $data['default_value']   ?? null,
            'input_type'      => $data['input_type']      ?? null,
            'options'         => array_key_exists('options', $data) ? $data['options'] : '__skip__',
            'sort_order'      => $data['sort_order']      ?? null,
            'is_required'     => array_key_exists('is_required', $data) ? (bool) $data['is_required'] : null,
            'is_active'       => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
        ], fn ($v) => $v !== '__skip__' && !is_null($v)));

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
