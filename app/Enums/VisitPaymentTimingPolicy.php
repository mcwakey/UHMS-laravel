<?php

namespace App\Enums;

enum VisitPaymentTimingPolicy: string
{
    case INHERIT = 'inherit';
    case PAY_BEFORE_SERVICE = 'pay_before_service';
    case PAY_AFTER_ALL_SERVICES = 'pay_after_all_services';
    case RUNNING_BILL = 'running_bill';

    public function translationKey(): string
    {
        return 'payment_timing.policies.'.$this->value.'.label';
    }

    public function label(): string
    {
        return __($this->translationKey());
    }

    public function description(): string
    {
        return __('payment_timing.policies.'.$this->value.'.description');
    }

    /** @return array<int, self> */
    public static function selectable(): array
    {
        return self::cases();
    }

    /** @return array<int, self> */
    public static function operationalPolicies(): array
    {
        return array_values(array_filter(self::cases(), fn (self $policy) => $policy !== self::INHERIT));
    }
}
