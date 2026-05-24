<?php

namespace App\Http\Requests\Concerns;

use App\Enums\DepartmentType;
use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Validation\Validator;

trait ValidatesVisitServiceRoutes
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $services = collect($this->input('services', []))
                ->filter(fn ($row) => is_array($row) && ! empty($row['service_catalog_id']));

            if ($services->isEmpty()) {
                return;
            }

            $catalogs = ServiceCatalog::with(['department', 'specialties'])
                ->whereIn('id', $services->pluck('service_catalog_id')->unique()->values())
                ->get()
                ->keyBy('id');

            $departmentIds = $services
                ->pluck('department_id')
                ->filter()
                ->unique()
                ->values();

            $departments = Department::whereIn('id', $departmentIds)
                ->get()
                ->keyBy('id');

            $services->each(function (array $serviceData, int|string $index) use ($validator, $catalogs, $departments) {
                $catalog = $catalogs->get((int) $serviceData['service_catalog_id']);
                if (! $catalog) {
                    return;
                }

                $departmentId = ! empty($serviceData['department_id'])
                    ? (int) $serviceData['department_id']
                    : (int) $catalog->department_id;

                if ($departmentId && ! in_array($departmentId, $catalog->getDepartmentIds(), true)) {
                    $validator->errors()->add(
                        "services.{$index}.department_id",
                        'The selected service does not belong to the selected department.'
                    );

                    return;
                }

                $department = $departmentId
                    ? ($departments->get($departmentId) ?? $catalog->department)
                    : $catalog->department;

                $type = $department?->type ?? $catalog->department_type;
                $typeValue = $type instanceof DepartmentType ? $type->value : (string) $type;
                $doctorId = $serviceData['doctor_id'] ?? null;

                if ($typeValue !== DepartmentType::CONSULTATION->value || empty($doctorId)) {
                    return;
                }

                if (! $this->doctorIsLinkedToDepartment((int) $doctorId, $departmentId)) {
                    $validator->errors()->add(
                        "services.{$index}.doctor_id",
                        'The selected doctor is not linked to this department through specialty.'
                    );
                }
            });
        });
    }

    private function doctorIsLinkedToDepartment(int $doctorId, int $departmentId): bool
    {
        if ($departmentId <= 0) {
            return false;
        }

        return User::query()
            ->whereKey($doctorId)
            ->where('status', UserStatus::ACTIVE->value)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', User::CONSULTATION_ROLES))
            ->whereHas('specialties', fn ($query) => $query->where('specialties.department_id', $departmentId))
            ->exists();
    }
}
