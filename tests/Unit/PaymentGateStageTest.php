<?php

namespace Tests\Unit;

use App\Data\Billing\PaymentGateContext;
use App\Enums\PaymentGateStage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentGateStageTest extends TestCase
{
    #[Test]
    public function stage_values_are_stable_and_localised(): void
    {
        $this->assertSame([
            'start', 'perform', 'result', 'complete', 'dispense', 'issue', 'render', 'readiness',
        ], array_column(PaymentGateStage::cases(), 'value'));

        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            foreach (PaymentGateStage::cases() as $stage) {
                $this->assertNotSame('payment_timing.gate_stages.'.$stage->value, $stage->label());
            }
        }
    }

    #[Test]
    public function named_contexts_use_machine_identifiers_and_bounded_observation_data(): void
    {
        $context = PaymentGateContext::laboratoryResultEntry(17);
        $this->assertSame(PaymentGateStage::RESULT, $context->stage);
        $this->assertSame('laboratory.result.enter', $context->operation);
        $this->assertSame([
            'payment_gate_stage' => 'result',
            'gate_operation' => 'laboratory.result.enter',
            'department_id' => 17,
            'department_type' => 'investigation',
            'service_type' => 'investigation_service',
            'invoice_item_present' => true,
            'emergency_stabilisation' => null,
        ], $context->observationContext(true));
    }
}
