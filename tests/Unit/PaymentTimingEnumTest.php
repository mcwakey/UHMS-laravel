<?php

namespace Tests\Unit;

use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTimingEnumTest extends TestCase
{
    #[Test]
    public function payment_timing_vocabulary_and_localised_labels_are_complete(): void
    {
        $this->assertSame(
            ['inherit', 'pay_before_service', 'pay_after_all_services', 'running_bill'],
            array_column(VisitPaymentTimingPolicy::cases(), 'value'),
        );
        $this->assertNotContains(VisitPaymentTimingPolicy::INHERIT, VisitPaymentTimingPolicy::operationalPolicies());

        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            foreach (VisitPaymentTimingPolicy::cases() as $policy) {
                $this->assertNotSame($policy->translationKey(), $policy->label());
            }
        }
    }

    #[Test]
    public function payment_policy_source_vocabulary_is_complete(): void
    {
        $this->assertSame([
            'global_default', 'visit_type', 'patient_risk', 'insurance',
            'corporate_account', 'manual_override', 'emergency_policy',
        ], array_column(VisitPaymentPolicySource::cases(), 'value'));
    }
}
