<?php

namespace App\Services;

use App\Models\Department;
use App\Models\StockLocation;
use RuntimeException;

/**
 * Single source of truth for "which stock location should I use?".
 *
 * Per the unified inventory rules:
 *  - All PO receipts enter Main Store.
 *  - Departments consume from their own department stock location only.
 */
class StockLocationService
{
    /**
     * Resolve the canonical Main Store location.
     *
     * Falls back to legacy heuristics (name = 'Main Store', or type = 'store')
     * to support pre-2026-05-18 deployments that did not have `is_main`.
     */
    public function getMainStoreLocation(): StockLocation
    {
        $main = StockLocation::query()
            ->where('is_main', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $main) {
            $main = StockLocation::query()
                ->where('name', 'Main Store')
                ->orWhere('type', 'store')
                ->orderBy('id')
                ->first();
        }

        if (! $main) {
            throw new RuntimeException(
                'No Main Store stock location configured. '
                . 'Seed one row in stock_locations with is_main = 1.'
            );
        }

        return $main;
    }

    /**
     * Resolve the active stock location for a department (used for consumption + transfers).
     *
     * Resolution order:
     *  1. Active stock_location where department_id = $department->id
     *  2. Active stock_location whose name matches the department name (legacy)
     *  3. null — caller decides whether to throw.
     */
    public function getDefaultLocationForDepartment(Department $department): ?StockLocation
    {
        $location = StockLocation::query()
            ->where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($location) {
            return $location;
        }

        return StockLocation::query()
            ->where('is_active', true)
            ->where('name', $department->name)
            ->orderBy('id')
            ->first();
    }

    /**
     * Same as above but throws if the department has no stock location yet.
     */
    public function requireDefaultLocationForDepartment(Department $department): StockLocation
    {
        $loc = $this->getDefaultLocationForDepartment($department);

        if (! $loc) {
            throw new RuntimeException(sprintf(
                'Department "%s" has no active stock location. Create one in Admin → Stock Locations.',
                $department->name,
            ));
        }

        return $loc;
    }

    /**
     * True when the given location is Main Store.
     */
    public function isMainStore(StockLocation $location): bool
    {
        if ($location->is_main) {
            return true;
        }

        $main = $this->getMainStoreLocation();

        return $main->id === $location->id;
    }
}
