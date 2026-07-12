<?php

namespace App\Services\Billing;

use App\Enums\VisitFinancialClearanceMode;
use App\Models\Setting;

class VisitFinancialClearanceConfigurationService
{
    public const GROUP = 'visit_financial_clearance';

    public function configuredMode(): VisitFinancialClearanceMode
    {
        return VisitFinancialClearanceMode::tryFrom((string) Setting::getValue(self::GROUP, 'mode', config('visit_financial_clearance.mode', 'disabled')))
            ?? VisitFinancialClearanceMode::DISABLED;
    }

    public function forceDisabled(): bool { return (bool) config('visit_financial_clearance.force_disabled', true); }
    public function effectiveMode(): VisitFinancialClearanceMode { return $this->forceDisabled() ? VisitFinancialClearanceMode::DISABLED : $this->configuredMode(); }
}
