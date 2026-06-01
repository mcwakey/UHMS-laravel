<?php

namespace App\Services;

use App\Models\ServiceRendering;
use App\Models\User;
use Illuminate\Support\Collection;

class ServiceRenderingReportService
{
    public function __construct(private ServiceRenderingQueryService $queries) {}

    public function summary(array $filters, ?User $user = null): array
    {
        $query = $this->queries->baseQuery($filters, $user);

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($statusCounts),
            'pending' => (int) ($statusCounts[ServiceRendering::STATUS_PENDING] ?? 0),
            'in_progress' => (int) ($statusCounts[ServiceRendering::STATUS_IN_PROGRESS] ?? 0),
            'rendered' => (int) ($statusCounts[ServiceRendering::STATUS_RENDERED] ?? 0),
            'not_rendered' => (int) ($statusCounts[ServiceRendering::STATUS_NOT_RENDERED] ?? 0),
            'cancelled' => (int) ($statusCounts[ServiceRendering::STATUS_CANCELLED] ?? 0),
            'on_hold' => (int) ($statusCounts[ServiceRendering::STATUS_ON_HOLD] ?? 0),
            'status_counts' => $statusCounts,
        ];
    }

    public function byDepartment(array $filters, ?User $user = null): Collection
    {
        return $this->queries->baseQuery($filters, $user)
            ->selectRaw('department_id, status, COUNT(*) as total')
            ->with('department')
            ->groupBy('department_id', 'status')
            ->get()
            ->groupBy(fn (ServiceRendering $rendering) => $rendering->department?->name ?? 'Unassigned');
    }

    public function byStaff(array $filters, ?User $user = null): Collection
    {
        return $this->queries->baseQuery($filters, $user)
            ->whereNotNull('rendered_by')
            ->selectRaw('rendered_by, COUNT(*) as total')
            ->with('renderedBy')
            ->groupBy('rendered_by')
            ->orderByDesc('total')
            ->get();
    }
}
