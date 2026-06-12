<?php

namespace App\Services;

use App\Models\User;

class ReportPermissionService
{
    public function canViewFinancialValues(User $user): bool
    {
        return $user->can('reports.financial_values.view');
    }

    public function canViewStockCost(User $user): bool
    {
        return $user->can('reports.stock_cost.view');
    }

    public function canViewSensitiveClinical(User $user): bool
    {
        return $user->can('reports.clinical_sensitive.view');
    }
}
