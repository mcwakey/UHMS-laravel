<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\StockLocation;

class StockLocationResolver
{
    /**
     * Resolve the default stock location for a given department.
     * Strategy:
     *   1. exact department_id match (prefer active)
     *   2. for known department types, match by stock_locations.type
     *   3. fallback to Main Store (type=store, is_active=true)
     */
    public function getDefaultLocationForDepartment(?Department $department): ?StockLocation
    {
        if ($department) {
            $direct = StockLocation::where('department_id', $department->id)
                ->where('is_active', true)
                ->first();
            if ($direct) return $direct;

            $type = $department->type instanceof DepartmentType
                ? $department->type->value
                : (string) $department->type;

            $byType = match ($type) {
                DepartmentType::PHARMACY->value      => 'pharmacy',
                DepartmentType::INVESTIGATION->value => 'lab',
                DepartmentType::PROCEDURE->value     => 'theatre',
                DepartmentType::TREATMENT->value     => 'ward',
                default                              => null,
            };
            if ($byType) {
                $match = StockLocation::where('type', $byType)->where('is_active', true)->first();
                if ($match) return $match;
            }
        }

        return StockLocation::where('type', 'store')->where('is_active', true)->first();
    }
}
