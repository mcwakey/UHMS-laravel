<?php

namespace Tests\Feature\Journey;

use App\Enums\JourneyDelayCause;
use App\Services\Journey\JourneySlaService;
use Tests\TestCase;

class JourneySlaTest extends TestCase
{
    private function slaStatus(JourneyDelayCause $cause, int $elapsed): string
    {
        return app(JourneySlaService::class)->evaluate($cause, $elapsed)['sla_status'];
    }

    public function test_sla_minutes_come_from_config(): void
    {
        $this->assertSame(120, app(JourneySlaService::class)->slaFor(JourneyDelayCause::AWAITING_LAB_RESULT));
        $this->assertSame(30, app(JourneySlaService::class)->slaFor(JourneyDelayCause::AWAITING_PAYMENT));
    }

    public function test_within_sla(): void
    {
        $this->assertSame(JourneySlaService::WITHIN, $this->slaStatus(JourneyDelayCause::AWAITING_LAB_RESULT, 50));
    }

    public function test_near_breach_at_eighty_percent(): void
    {
        // 120 * 0.8 = 96
        $this->assertSame(JourneySlaService::NEAR_BREACH, $this->slaStatus(JourneyDelayCause::AWAITING_LAB_RESULT, 100));
    }

    public function test_breached_when_sla_exceeded(): void
    {
        $this->assertSame(JourneySlaService::BREACHED, $this->slaStatus(JourneyDelayCause::AWAITING_LAB_RESULT, 130));
    }

    public function test_critical_breach_at_double_sla(): void
    {
        $this->assertSame(JourneySlaService::CRITICAL_BREACH, $this->slaStatus(JourneyDelayCause::AWAITING_LAB_RESULT, 250));
    }

    public function test_minutes_to_breach_is_signed(): void
    {
        $this->assertSame(70, app(JourneySlaService::class)->evaluate(JourneyDelayCause::AWAITING_LAB_RESULT, 50)['minutes_to_breach']);
        $this->assertSame(-10, app(JourneySlaService::class)->evaluate(JourneyDelayCause::AWAITING_LAB_RESULT, 130)['minutes_to_breach']);
    }

    public function test_rank_orders_worst_first(): void
    {
        $sla = app(JourneySlaService::class);
        $this->assertGreaterThan($sla->rank(JourneySlaService::BREACHED), $sla->rank(JourneySlaService::CRITICAL_BREACH));
        $this->assertGreaterThan($sla->rank(JourneySlaService::NEAR_BREACH), $sla->rank(JourneySlaService::BREACHED));
        $this->assertGreaterThan($sla->rank(JourneySlaService::WITHIN), $sla->rank(JourneySlaService::NEAR_BREACH));
    }
}
