<?php

namespace App\Data\Billing;

use App\Enums\VisitPaymentTimingPolicy;

final readonly class LegacyPaymentGateSnapshot
{
    public function __construct(
        public ?bool $allowed,
        public bool $advisory,
        public bool $comparable,
        public ?VisitPaymentTimingPolicy $expectedPolicy,
        public string $reasonCode,
        public ?string $mode = null,
        public array $context = [],
    ) {}
}
