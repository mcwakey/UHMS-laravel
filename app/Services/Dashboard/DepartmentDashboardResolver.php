<?php

namespace App\Services\Dashboard;

use App\Enums\DepartmentType;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

/**
 * Decides which department-type dashboard a user should see.
 *
 * Resolution order:
 *   1. Global admin / management roles            → management
 *   2. The user's primary department TYPE         → matching dashboard
 *   3. Role-based fallback (billing/stock/…)       → matching dashboard
 *   4. Generic staff dashboard
 *
 * Returns a stable string key consumed by {@see DepartmentDashboardService}.
 */
class DepartmentDashboardResolver
{
    public const MANAGEMENT = 'management';
    public const CONSULTATION = 'consultation';
    public const PHARMACY = 'pharmacy';
    public const INVESTIGATION = 'investigation';
    public const THEATRE = 'theatre';
    public const BILLING = 'billing';
    public const STOCK = 'stock';
    public const ACCOUNTING = 'accounting';
    public const EMERGENCY = 'emergency';
    public const ADMISSION = 'admission';
    public const BLOOD_BANK = 'blood_bank';
    public const CLAIMS = 'claims';
    public const HR = 'hr';
    public const RECEPTION = 'reception';
    public const GENERIC = 'generic';

    /**
     * @return array{key:string, label:string}
     */
    public function resolve(User $user): array
    {
        $key = $this->resolveKey($user);

        return ['key' => $key, 'label' => $this->labelFor($key)];
    }

    public function resolveKey(User $user): string
    {
        // 1. Management / admin always sees the hospital-wide dashboard.
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Hospital Administrator', 'Management', 'Medical Director'])) {
            return self::MANAGEMENT;
        }

        // 2. Department type, when the user is assigned to a department.
        $type = $user->department?->type;
        if ($type instanceof DepartmentType) {
            $byType = match ($type) {
                DepartmentType::CONSULTATION, DepartmentType::TREATMENT => self::CONSULTATION,
                DepartmentType::EMERGENCY => self::EMERGENCY,
                DepartmentType::PHARMACY => self::PHARMACY,
                DepartmentType::INVESTIGATION, DepartmentType::RADIOLOGY => self::INVESTIGATION,
                DepartmentType::PROCEDURE, DepartmentType::THEATRE => self::THEATRE,
                DepartmentType::INPATIENT, DepartmentType::MATERNITY, DepartmentType::NURSING => self::ADMISSION,
                DepartmentType::BLOOD_BANK => self::BLOOD_BANK,
                DepartmentType::RECORDS => self::RECEPTION,
                DepartmentType::FINANCE => self::ACCOUNTING,
                DepartmentType::STORES => self::STOCK,
                DepartmentType::ADMINISTRATIVE => self::MANAGEMENT,
                // support, mortuary, ambulance, and any future type fall through
                // to the role-based fallback, then the generic dashboard.
                default => null,
            };
            if ($byType) {
                return $byType;
            }
        }

        // 3. Role-based fallback for finance/stock/clinical roles whose
        //    department type does not map cleanly (or who have no department).
        return $this->resolveByRole($user) ?? self::GENERIC;
    }

    private function resolveByRole(User $user): ?string
    {
        $map = [
            self::EMERGENCY => ['Emergency Doctor', 'Emergency Nurse', 'Triage Nurse', 'Casualty Medical Officer'],
            self::RECEPTION => ['Receptionist', 'Front Desk Officer', 'Records Officer'],
            self::ADMISSION => ['Ward Nurse', 'Chief Nursing Officer', 'Ward Manager'],
            self::BLOOD_BANK => ['Blood Bank Officer', 'Blood Bank Technician'],
            self::CLAIMS => ['Claims Officer', 'Insurance Officer', 'NHIS Officer'],
            self::HR => ['HR Manager', 'HR Officer'],
            self::BILLING => ['Cashier', 'Billing Officer', 'Billing Manager'],
            self::ACCOUNTING => ['Accountant', 'Finance Officer', 'Finance Manager'],
            self::STOCK => ['Store Keeper', 'Storekeeper', 'Store Manager', 'Procurement Officer', 'Inventory Manager'],
            self::PHARMACY => ['Pharmacist', 'Pharmacy Technician', 'Dispenser'],
            self::INVESTIGATION => ['Lab Technician', 'Laboratory Technician', 'Lab Manager', 'Radiologist', 'Lab Scientist', 'Ultrasound Technician'],
            self::THEATRE => ['Surgeon', 'General Surgeon', 'Theatre Nurse', 'Anaesthetist', 'Scrub Nurse', 'Scrub Technician'],
            self::CONSULTATION => ['Doctor', 'Consultant', 'Medical Officer', 'Senior Medical Officer', 'House Officer'],
        ];

        foreach ($map as $key => $roles) {
            if ($user->hasAnyRole($roles)) {
                return $key;
            }
        }

        return null;
    }

    public function labelFor(string $key): string
    {
        $transKey = 'dashboards.titles.'.$key;
        if (Lang::has($transKey)) {
            return __($transKey);
        }

        return __('dashboards.titles.generic');
    }
}
