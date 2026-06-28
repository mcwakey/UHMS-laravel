<?php

namespace App\Services\Department;

use App\Models\User;
use BackedEnum;

/**
 * Decides whether a user may SEE a dashboard capability (a metric/widget/action/
 * drilldown domain). A single permission name can't represent every clinical user
 * (a doctor reads visits via consultation permissions, not `visits.view`), so each
 * capability is satisfied by EITHER:
 *
 *   1. any one of a set of module permissions, OR
 *   2. membership of a department whose TYPE owns that capability (workflow access —
 *      the department you work in already grants you visibility of its board).
 *
 * Super Admins see everything. Resolution uses only already-loaded relations and the
 * cached permission registrar, so it adds no dashboard queries.
 */
class DepartmentDashboardCapabilityService
{
    /** capability => ['permissions' => list<string>, 'types' => list<string>] */
    public const PROFILES = [
        'consultation_access' => [
            'permissions' => ['visits.view', 'consultations.view', 'consultation.access', 'consultation.queue', 'queue.view', 'queue.manage', 'appointments.view'],
            'types' => ['consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records'],
        ],
        'investigation_access' => [
            'permissions' => ['lab.requests.view', 'lab.results.view', 'consultation.request_lab'],
            'types' => ['investigation', 'radiology', 'blood_bank'],
        ],
        'pharmacy_access' => [
            'permissions' => ['prescriptions.view', 'pharmacy.dispensing.view', 'pharmacy.stock.manage'],
            'types' => ['pharmacy'],
        ],
        'stock_access' => [
            'permissions' => ['store.purchase.view', 'pharmacy.stock.manage', 'store.requisition.view'],
            'types' => ['pharmacy', 'stores', 'blood_bank'],
        ],
        'ward_access' => [
            'permissions' => ['ward.view', 'beds.view', 'ward.admit'],
            'types' => ['inpatient', 'maternity', 'nursing', 'treatment'],
        ],
        'emergency_access' => [
            'permissions' => ['emergency.case.view', 'emergency.board.view'],
            'types' => ['emergency', 'ambulance'],
        ],
        'financial_access' => [
            'permissions' => ['reports.financial_values.view', 'invoices.view', 'payments.view', 'billing.view'],
            'types' => ['finance', 'administrative'],
        ],
    ];

    /** A null capability means "not gated" (low-sensitivity, always visible). */
    public function can(User $user, ?string $capability): bool
    {
        if ($capability === null) {
            return true;
        }

        $profile = self::PROFILES[$capability] ?? null;
        if ($profile === null) {
            return true;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        foreach ($profile['permissions'] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return $this->memberOfType($user, $profile['types']);
    }

    /** The set of capabilities a user holds — used by the role matrix / docs. */
    public function capabilitiesFor(User $user): array
    {
        return array_values(array_filter(
            array_keys(self::PROFILES),
            fn (string $capability) => $this->can($user, $capability),
        ));
    }

    private function memberOfType(User $user, array $types): bool
    {
        $primary = $user->department?->type;
        if ($this->typeMatches($primary, $types)) {
            return true;
        }

        if ($user->relationLoaded('departments')) {
            foreach ($user->departments as $department) {
                if ($this->typeMatches($department->type, $types)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function typeMatches(mixed $type, array $types): bool
    {
        $value = $type instanceof BackedEnum ? $type->value : $type;

        return is_string($value) && in_array($value, $types, true);
    }
}
