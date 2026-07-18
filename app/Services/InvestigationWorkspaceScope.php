<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\LabRequest;
use App\Models\Sample;
use App\Services\Department\DepartmentContextSwitcherService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Centralises the active-department boundary for the Investigations browser
 * workspace. Diagnostic requests are scoped by their TARGET department (the
 * laboratory/section performing the work). Existing lab services remain
 * reusable; only their query boundary changes.
 */
class InvestigationWorkspaceScope
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

        return $type === DepartmentType::INVESTIGATION ? (int) $department->id : null;
    }

    public function active(): bool
    {
        return $this->departmentId() !== null;
    }

    public function labRequests(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId ? $query->where('target_department_id', $departmentId) : $query;
    }

    public function samples(Builder $query): Builder
    {
        $departmentId = $this->departmentId();

        return $departmentId
            ? $query->whereHas('labRequest', fn (Builder $request) => $request->where('target_department_id', $departmentId))
            : $query;
    }

    /** Whether a single request belongs to the active investigation department. */
    public function contains(LabRequest $labRequest): bool
    {
        $departmentId = $this->departmentId();

        return $departmentId === null || (int) $labRequest->target_department_id === $departmentId;
    }

    public function containsSample(Sample $sample): bool
    {
        $departmentId = $this->departmentId();
        if ($departmentId === null) {
            return true;
        }

        $request = $sample->relationLoaded('labRequest') ? $sample->labRequest : $sample->labRequest()->first();

        return $request !== null && (int) $request->target_department_id === $departmentId;
    }
}
