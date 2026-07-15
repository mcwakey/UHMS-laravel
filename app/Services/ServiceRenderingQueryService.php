<?php

namespace App\Services;

use App\Enums\VisitType;
use App\Models\ServiceRendering;
use App\Models\User;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ServiceRenderingQueryService
{
    public function paginate(array $filters, ?User $user = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters, $user)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function baseQuery(array $filters, ?User $user = null): Builder
    {
        $query = ServiceRendering::query()
            ->with([
                'patient',
                'visit',
                'invoiceItem.invoice',
                'service',
                'department',
                'emergencyCase',
                'admission',
                'renderedBy',
                'startedBy',
            ]);

        if (app(WorkspaceRouteResolver::class)->isNursing() && $user) {
            $department = app(DepartmentContextSwitcherService::class)->currentDepartment($user, request());
            $query->where('department_id', $department?->id)
                ->whereNull('admission_id')
                ->whereNull('emergency_case_id')
                ->whereHas('visit', fn (Builder $visit) => $visit->where('visit_type', VisitType::OUTPATIENT->value));
        } else {
            $query->visibleTo($user);
        }

        return $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['department_id'] ?? null, fn (Builder $query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['service_id'] ?? null, fn (Builder $query, $serviceId) => $query->where('service_id', $serviceId))
            ->when($filters['rendered_by'] ?? null, fn (Builder $query, $renderedBy) => $query->where('rendered_by', $renderedBy))
            ->when($filters['payment_status'] ?? null, function (Builder $query, string $status) {
                $query->whereHas('invoiceItem', fn (Builder $itemQuery) => $itemQuery->where('payment_status', $status));
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['source'] ?? null, function (Builder $query, string $source) {
                if ($source === 'emergency') {
                    $query->whereNotNull('emergency_case_id');
                } elseif ($source === 'admission') {
                    $query->whereNotNull('admission_id');
                } elseif ($source === 'consultation') {
                    $query->whereNotNull('consultation_route_id');
                } elseif ($source === 'opd') {
                    $query->whereNull('emergency_case_id')->whereNull('admission_id');
                }
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->whereHas('patient', function (Builder $patientQuery) use ($search) {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('patient_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('visit', fn (Builder $visitQuery) => $visitQuery->where('visit_number', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn (Builder $serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('invoiceItem.invoice', fn (Builder $invoiceQuery) => $invoiceQuery->where('invoice_number', 'like', "%{$search}%"));
                });
            });
    }
}
