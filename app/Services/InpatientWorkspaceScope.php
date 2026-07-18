<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Bed;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\ProcedureRequest;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Centralises the active-ward boundary for the Inpatient browser workspace.
 * Existing domain services remain reusable; only their query boundary changes.
 */
class InpatientWorkspaceScope
{
    public function __construct(
        private DepartmentContextSwitcherService $departments,
        private Request $request,
    ) {}

    public function departmentId(): ?int
    {
        $user = $this->request->user();
        if (! $user) {
            return null;
        }

        $department = $this->departments->currentDepartment($user, $this->request);
        $type = $department?->type instanceof DepartmentType
            ? $department->type
            : DepartmentType::tryFrom((string) ($department?->type ?? ''));

        return $type === DepartmentType::INPATIENT ? (int) $department->id : null;
    }

    public function active(): bool
    {
        return $this->departmentId() !== null;
    }

    public function admissions(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('bed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
            : $query;
    }

    public function admissionRequests(Builder $query): Builder
    {
        $departmentId = $this->departmentId();
        if (! $departmentId) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($departmentId) {
            $scope->whereNull('requested_ward_id')
                ->orWhereHas('requestedWard', fn (Builder $ward) => $ward->where('department_id', $departmentId))
                ->orWhereHas('reservedBed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId));
        });
    }

    public function wards(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId ? $query->where('department_id', $departmentId) : $query;
    }

    public function beds(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
            : $query;
    }

    public function visits(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('admission.bed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
            : $query;
    }

    public function patients(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('admissions.bed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
            : $query;
    }

    public function labRequests(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('visit.admission.bed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
            : $query;
    }

    public function procedureRequests(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('visit.admission.bed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
            : $query;
    }

    public function contains(mixed $resource): bool
    {
        $departmentId = $this->departmentId();
        if (! $departmentId) {
            return true;
        }

        return match (true) {
            $resource instanceof Ward => (int) $resource->department_id === $departmentId,
            $resource instanceof Bed => (int) $resource->ward()->value('department_id') === $departmentId,
            $resource instanceof Admission => (int) $resource->bed?->ward?->department_id === $departmentId,
            $resource instanceof AdmissionRequest => $resource->requested_ward_id === null
                || (int) $resource->requestedWard?->department_id === $departmentId
                || (int) $resource->reservedBed?->ward?->department_id === $departmentId,
            $resource instanceof Visit => $resource->admission !== null
                && (int) $resource->admission?->bed?->ward?->department_id === $departmentId,
            $resource instanceof LabRequest => $resource->visit !== null
                && (int) $resource->visit?->admission?->bed?->ward?->department_id === $departmentId,
            $resource instanceof ProcedureRequest => $resource->visit !== null
                && (int) $resource->visit?->admission?->bed?->ward?->department_id === $departmentId,
            $resource instanceof Patient => $resource->admissions()
                ->whereHas('bed.ward', fn (Builder $ward) => $ward->where('department_id', $departmentId))
                ->exists(),
            default => true,
        };
    }
}
