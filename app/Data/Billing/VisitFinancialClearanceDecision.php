<?php

namespace App\Data\Billing;

use App\Enums\VisitFinancialClearanceBasis;
use App\Enums\VisitFinancialClearanceStatus;

final readonly class VisitFinancialClearanceDecision
{
    public function __construct(
        public VisitFinancialClearanceStatus $status,
        public ?VisitFinancialClearanceBasis $basis,
        public bool $mayFinanciallyClose,
        public bool $requiresFinanceAction,
        public string $reasonCode,
        public VisitFinancialSummary $summary,
        public ?int $exceptionId = null,
    ) {}
}
