<?php

namespace App\Services\Dashboard;

use App\Enums\DepartmentType;

/**
 * Declarative registry mapping a department TYPE to the dashboard it should use,
 * plus the list of dashboards an admin may preview. This is the single source of
 * truth for "which dashboard does this department type get" — keep it here rather
 * than hardcoding the mapping in the resolver, controller, or Blade view.
 *
 * A type that maps to `null` (and any future, unmapped type) deliberately falls
 * through to the role-based fallback, then the generic dashboard — never a crash.
 */
class DepartmentDashboardRegistry
{
    /** Department type value → dashboard key (null = fall through to role/generic). */
    private const TYPE_TO_KEY = [
        'consultation'   => DepartmentDashboardResolver::CONSULTATION,
        'treatment'      => DepartmentDashboardResolver::CONSULTATION,
        'nursing'        => DepartmentDashboardResolver::ADMISSION,
        'emergency'      => DepartmentDashboardResolver::EMERGENCY,
        'investigation'  => DepartmentDashboardResolver::INVESTIGATION,
        'radiology'      => DepartmentDashboardResolver::INVESTIGATION,
        'procedure'      => DepartmentDashboardResolver::THEATRE,
        'theatre'        => DepartmentDashboardResolver::THEATRE,
        'pharmacy'       => DepartmentDashboardResolver::PHARMACY,
        'inpatient'      => DepartmentDashboardResolver::ADMISSION,
        'maternity'      => DepartmentDashboardResolver::ADMISSION,
        'blood_bank'     => DepartmentDashboardResolver::BLOOD_BANK,
        'records'        => DepartmentDashboardResolver::RECEPTION,
        'finance'        => DepartmentDashboardResolver::ACCOUNTING,
        'stores'         => DepartmentDashboardResolver::STOCK,
        'administrative' => DepartmentDashboardResolver::MANAGEMENT,
        // mortuary, ambulance, support → null (no dedicated dashboard yet).
    ];

    /** Dashboards an admin can preview via ?as=, in display order. */
    private const PREVIEWABLE = [
        DepartmentDashboardResolver::MANAGEMENT,
        DepartmentDashboardResolver::CONSULTATION,
        DepartmentDashboardResolver::EMERGENCY,
        DepartmentDashboardResolver::ADMISSION,
        DepartmentDashboardResolver::PHARMACY,
        DepartmentDashboardResolver::INVESTIGATION,
        DepartmentDashboardResolver::THEATRE,
        DepartmentDashboardResolver::BILLING,
        DepartmentDashboardResolver::CLAIMS,
        DepartmentDashboardResolver::STOCK,
        DepartmentDashboardResolver::BLOOD_BANK,
        DepartmentDashboardResolver::ACCOUNTING,
        DepartmentDashboardResolver::HR,
        DepartmentDashboardResolver::RECEPTION,
        DepartmentDashboardResolver::GENERIC,
    ];

    /**
     * The dashboard key for a department type, or null to fall through.
     */
    public function keyForType(?DepartmentType $type): ?string
    {
        return $type ? (self::TYPE_TO_KEY[$type->value] ?? null) : null;
    }

    /**
     * @return list<string> dashboard keys an admin may preview, in display order.
     */
    public function previewableKeys(): array
    {
        return self::PREVIEWABLE;
    }

    public function isPreviewable(string $key): bool
    {
        return in_array($key, self::PREVIEWABLE, true);
    }
}
