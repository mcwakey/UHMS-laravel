<?php

namespace App\Services;

use App\Models\Department;
use App\Models\StockLocation;

/**
 * Keeps a department's "Store manages stock" flag and its physical stock
 * location in sync, from whichever side the change originates:
 *
 *  - Turning ON a department's stock management auto-creates a stock location
 *    for it (if one does not already exist).
 *  - Creating/assigning a stock location to a department flips that
 *    department's "Store manages stock" flag to true.
 *
 * Calls are explicit (from the controllers), so the two directions cannot
 * recurse — each is additionally guarded by an existence check.
 */
class StockLocationSyncService
{
    /**
     * Ensure a stock-managed department has a stock location. No-op when the
     * department is not stock managed or already has a location.
     */
    public function ensureDepartmentLocation(Department $department): ?StockLocation
    {
        if (! $department->is_stock_managed) {
            return null;
        }

        $existing = StockLocation::where('department_id', $department->id)->first();
        if ($existing) {
            return $existing;
        }

        return StockLocation::create([
            'name'          => $this->uniqueLocationName($department),
            'type'          => 'department',
            'department_id' => $department->id,
            'is_active'     => true,
            'is_main'       => false,
            'notes'         => 'Auto-created for stock-managed department.',
        ]);
    }

    /**
     * When a location belongs to a department, make sure that department is
     * flagged as stock managed.
     */
    public function markDepartmentManaged(StockLocation $location): void
    {
        if (! $location->department_id) {
            return;
        }

        $department = $location->department ?: Department::find($location->department_id);
        if ($department && ! $department->is_stock_managed) {
            $department->update(['is_stock_managed' => true]);
        }
    }

    /**
     * stock_locations.name is unique, so derive a non-colliding name.
     */
    private function uniqueLocationName(Department $department): string
    {
        $candidates = [
            $department->name,
            trim($department->name.' '.($department->code ?? '')),
            $department->name.' Store',
            $department->name.' Store #'.$department->id,
        ];

        foreach ($candidates as $name) {
            $name = trim($name);
            if ($name !== '' && ! StockLocation::where('name', $name)->exists()) {
                return $name;
            }
        }

        return $department->name.' #'.$department->id;
    }
}
