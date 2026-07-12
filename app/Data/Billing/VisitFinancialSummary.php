<?php

namespace App\Data\Billing;

final readonly class VisitFinancialSummary
{
    public function __construct(
        public string $currency,
        public string $patientResponsibility,
        public string $patientPaid,
        public string $patientOutstanding,
        public string $insuranceResponsibility,
        public string $sponsorResponsibility,
        public string $corporateResponsibility,
        public int $invoiceCount,
        public int $invoiceItemCount,
        public int $receivableCount,
        public bool $hasUnbilledBillableItems,
        public bool $hasPendingFinancialAdjustments,
        public array $context = [],
    ) {}

    public function snapshot(): array
    {
        return get_object_vars($this);
    }
}
